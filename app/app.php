<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 9/6/2015
 * Time: 6:59 PM
 */

use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/Tim/views',
));

$app['twig'] = $app->share($app->extend('twig', function($twig, $app) {
    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
        return sprintf('%s/%s', trim($app['request']->getBasePath()), ltrim($asset, '/'));
    }));
    return $twig;
}));

$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new Silex\Provider\TranslationServiceProvider(), array('translator.domains' => array(),));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());
$app->register(new FormServiceProvider());

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('active', $request->get("_route"));
});

$app->get('/json', function() use ($app) {
    return $app['twig']->render('json.html.twig');
})->bind('json');

$app->match('/password', function(Request $request) use ($app) {

    $result = null;
    $default = array(
        'count' => 10,
        'length' => 10
    );

    $form = $app['form.factory']->createBuilder('form', $default)
        ->add('count', 'integer', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Range(array('min' => 1, 'max' => 100))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Count')
        ))
        ->add('length', 'integer', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Range(array('min' => 1, 'max' => 100))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Password Length')
        ))
        ->add('usingChars', 'checkbox', array('required' => false,
            'constraints' => array(new Assert\Type(array('type' => 'bool'))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Using chars', 'style' => 'max-width: 100px')
        ))
        ->add('usingSpecialChars', 'checkbox', array('required' => false,
            'constraints' => array(new Assert\Type(array('type' => 'bool'))),
            'attr' => array(
                'class' => 'form-control', 'placeholder' => 'Using special chars',
                'style' => 'max-width: 100px')
        ))
        ->add('send', 'submit', array('label' => 'Generate Passwords',
            'attr' => array('class' => 'btn btn-default')
        ))
        ->getForm();

    $form->handleRequest($request);

    if($form->isValid()) {
        $data = $form->getData();

        $sets = array();
        $sets[] = '123456789';
        if($data['usingChars'])
            $sets[] = 'abcdefghjkmnpqrstuvwxyz';
        if($data['usingChars'])
            $sets[] = 'ABCDEFGHJKMNPQRSTUVWXYZ';
        if($data['usingSpecialChars'])
            $sets[] = '!@#$%&*?';

        function generatePassword($length, $sets)
        {
            $all = '';
            $password = '';
            foreach($sets as $set)
            {
                $password .= $set[array_rand(str_split($set))];
                $all .= $set;
            }
            $all = str_split($all);
            for($i = 0; $i < $length - count($sets); $i++)
                $password .= $all[array_rand($all)];
            $password = str_shuffle($password);

            return $password;
        }

        $count = $data['count'];
        $length = $data['length'];

        for($i = 0; $i < $count; $i++) {
            $result[] = generatePassword($length, $sets);
        }
    }

    return $app['twig']->render('password.html.twig', array('form' => $form->createView(), 'result' => $result));
})->bind('password');

$app->match('/base64', function(Request $request) use ($app) {

    $result = null;
    $default = array();

    $choices = array('fromBase64' => 'Base64 to String', 'toBase64' => 'String to Base64');
    $form = $app['form.factory']->createBuilder('form', $default)
        ->add('text', 'textarea', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 3, 'max' => '800000'))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Text')
        ))
        ->add('fromBase64', 'choice', array(
            'choices'  => $choices,
            'constraints' => array(new Assert\Choice(array('choices' => array_keys($choices)))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Choose an option'),
            'required' => true,
        ))
        ->add('send', 'submit', array('label' => 'Convert',
            'attr' => array('class' => 'btn btn-default')
        ))
        ->getForm();

    $form->handleRequest($request);

    if($form->isValid()) {
        $data = $form->getData();

        $value = $data['fromBase64'];
        if ($value == 'fromBase64') {
            $result = base64_decode($data['text']);
        } else {
            $result = base64_encode($data['text']);
        }
    }

    return $app['twig']->render('base64.html.twig', array('form' => $form->createView(), 'result' => $result));
})->bind('base64');

$app->match('/random', function(Request $request) use ($app) {

    $result = null;
    $default = array(
        'length' => 64
    );

    $form = $app['form.factory']->createBuilder('form', $default)
        ->add('length', 'integer', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Range(array('min' => 1))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Length Random Text')
        ))
        ->add('send', 'submit', array('label' => 'Generate',
            'attr' => array('class' => 'btn btn-default')
        ))
        ->getForm();

    $form->handleRequest($request);

    if($form->isValid()) {
        $data = $form->getData();

        function getRand($length) {
            switch (true) {
                case function_exists("mcrypt_create_iv") :
                    if (PHP_VERSION_ID >= 50300) {
                        $r = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
                    } else {
                        $r = mcrypt_create_iv($length, MCRYPT_RAND);
                    }
                    break;
                case function_exists("openssl_random_pseudo_bytes") :
                    $r = openssl_random_pseudo_bytes($length);
                    break;
                case is_readable('/dev/urandom') : // deceze
                    $r = file_get_contents('/dev/urandom', false, null, 0, $length);
                    break;
                default :
                    $i = 0;
                    $r = "";
                    while($i ++ < $length) {
                        $r .= chr(mt_rand(0, 255));
                    }
                    break;
            }
            return substr(bin2hex($r), 0, $length);
        }

        $result = getRand($data['length']);
    }

    return $app['twig']->render('random.html.twig', array('form' => $form->createView(), 'result' => $result));
})->bind('random');


$app->match('/', function(Request $request) use ($app) {

    $hash = false;
	$default = array(
        'name' => '',
        'email' => '',
        'message' => '',
    );
    $result = array();
    $time = null;

    $form = $app['form.factory']->createBuilder('form', $default)
        ->add('text', 'text', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 3, 'max' => '4096'))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Text')
        ))
        ->add('salt', 'text', array('required' => false,
            'constraints' => new Assert\Length(array('min' => 0, 'max' => '4096')),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Salt')
        ))
        ->add('send', 'submit', array('label' => 'Generate',
            'attr' => array('class' => 'btn btn-default')
        ))
        ->getForm();

    $form->handleRequest($request);

    if($form->isValid()) {
        $data = $form->getData();

        $text = $data['text'];
        $salt = $data['salt'];

        $algorithms = array();
        foreach (hash_algos() as $v) {
            $algorithms[] = array($v => array('Text' => '(Text)', 'Value' => $text));
            $algorithms[] = array($v => array('Text' => '(Text.Salt)', 'Value' => $text.$salt));
            $algorithms[] = array($v => array('Text' => '(Salt.Text)', 'Value' => $salt.$text));
        }

        $time = microtime(true);
        foreach($algorithms as $alg) {
            foreach($alg as $key => $value) {
                if (function_exists($key)) {
                    $result[] = array('alg' => $key . $value['Text'], 'res' => call_user_func($key, $value['Value']));
                } else {
                    $result[] = array('alg' => $key . $value['Text'], 'res' => hash($key, $value['Value']));
                }
            }
        }
        $time = microtime(true) - $time;

        $hash = true;
    }

    return $app['twig']->render('hash.html.twig', array('form' => $form->createView(), 'hash' => $hash,
        'result' => $result, 'time' => $time));
})->bind('hash');

return $app;
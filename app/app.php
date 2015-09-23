<?php
/**
 * Created by PhpStorm.
 * User: tim
 * Date: 9/6/2015
 * Time: 6:59 PM
 */

use Silex\Provider\FormServiceProvider;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'translator.domains' => array(),
));
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new FormServiceProvider());

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('active', $request->get("_route"));
});

$app->before(function (Request $request) use ($app) {
    $app['twig']->addGlobal('active', $request->get("_route"));
});

$app->get('/json', function() use ($app) {
    return $app['twig']->render('layout.html.twig');
})->bind('json');

$app->get('/base64', function() use ($app) {
    return $app['twig']->render('layout.html.twig');
})->bind('base64');

$app->match('/', function(Request $request) use ($app) {

    $hash = false;
	$default = array(
        'name' => '',
        'email' => '',
        'message' => '',
    );
    $result = array();

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

        // todo: rewrite to expressions:

        $algorithms = array();
        $algorithms[] = array('md5' => array('Text' => '(Text)', 'Value' => $text));
        $algorithms[] = array('md5' => array('Text' => '(Text.Salt)', 'Value' => $text.$salt));
        $algorithms[] = array('md5' => array('Text' => '(Salt.Text)', 'Value' => $salt.$text));
        $algorithms[] = array('sha1' => array('Text' => '(Text)', 'Value' => $text));
        $algorithms[] = array('sha1' => array('Text' => '(Text.Salt)', 'Value' => $text.$salt));
        $algorithms[] = array('sha1' => array('Text' => '(Salt.Textt)', 'Value' => $salt.$text));

        foreach($algorithms as $alg) {
            foreach($alg as $key => $value) {
                $result[] = array('alg' => $key.$value['Text'], 'res' => call_user_func($key, $value['Value']));
            }
        }

        $hash = true;
    }

    return $app['twig']->render('hash.html.twig', array('form' => $form->createView(), 'hash' => $hash,
        'result' => $result));
})->bind('hash');

return $app;
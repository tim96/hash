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

// web/index.php
require_once __DIR__.'/../vendor/autoload.php';

$app = new Silex\Application();

/*$app->register(new Tim\HelloServiceProvider(), array(
    'hello.default_name' => 'Tim',
));*/

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

// define controllers for a blog
/*$blog = $app['controllers_factory'];
$blog->get('/', function () {
    return 'Blog home page';
});

// define controllers for a forum
$forum = $app['controllers_factory'];
$forum->get('/', function () {
    return 'Forum home page';
});

$app->get('/', function () use ($app) {
    return 'Hello World';
});

$app->post('/hash', function (Request $request) use ($app) {
    $message = $request->get('message');
    // mail('feedback@yoursite.com', '[YourSite] Feedback', $message);

    return new Response('Thank you for your feedback! '.$app->escape($message), 201);
});
*/

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
        /*->add('message', 'textarea', array(
            'constraints' => array(new Assert\NotBlank(), new Assert\Length(array('min' => 20))),
            'attr' => array('class' => 'form-control', 'placeholder' => 'Enter Your Message')
        ))*/
        ->add('send', 'submit', array('label' => 'Generate',
            'attr' => array('class' => 'btn btn-default')
        ))
        ->getForm();

    $form->handleRequest($request);

    if($form->isValid()) {
        $data = $form->getData();

        /*$message = \Swift_Message::newInstance()
            ->setSubject('Llama Feedback')
            ->setFrom(array($data['email'] => $data['name']))
            ->setTo(array('feedback@lilyandlarryllamafarmers.com'))
            ->setBody($data['message']);
        */
        // $app['mailer']->send($message);

        $text = $data['text'];
        $salt = $data['salt'];

        $result[] = array('alg' => 'md5(Text)', 'res' => md5($text));
        $result[] = array('alg' => 'md5(Text.Salt)', 'res' => md5($text.$salt));

        $hash = true;
    }

    return $app['twig']->render('hash.html.twig', array('form' => $form->createView(), 'hash' => $hash,
        'result' => $result));
})->bind('hash');

/*$app->get('/hello', function () use ($app) {
    $name = $app['request']->get('name');

    return $app['hello']($name);
});*/

// $app->mount('/blog', $blog);
// $app->mount('/forum', $forum);

// $app->run();
return $app;
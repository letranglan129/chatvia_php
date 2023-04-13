<?php

namespace Config;

// Create a new instance of our RouteCollection class.
$routes = Services::routes();

/*
 * --------------------------------------------------------------------
 * Router Setup
 * --------------------------------------------------------------------
 */
$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
// The Auto Routing (Legacy) is very dangerous. It is easy to create vulnerable apps
// where controller filters or CSRF protection are bypassed.
// If you don't want to define all routes, please use the Auto Routing (Improved).
// Set `$autoRoutesImproved` to true in `app/Config/Feature.php` and set the following to true.
// $routes->setAutoRoute(false);

/*
 * --------------------------------------------------------------------
 * Route Definitions
 * --------------------------------------------------------------------
 */

// We get a performance increase by specifying the default
// route since we don't have to scan directories.
$routes->get('/','Home::index', ['filter' =>  'auth']);
$routes->get('/home','Home::index');
$routes->get('/login','User::loginView', ['filter' =>  'noauth']);
$routes->post('/login', 'User::login', ['filter' =>  'noauth']);
$routes->post('/signIn', 'User::signIn', ['filter' =>  'noauth']);
$routes->post('/signUp', 'User::signUp', ['filter' =>  'noauth']);
$routes->get('/register', 'User::registerView', ['filter' =>  'noauth']);
$routes->post('/register', 'User::register', ['filter' =>  'noauth']);
// $routes->get('/upload', 'Upload::index');
// $routes->post('/upload', 'Upload::uploadImage');
$routes->get('/user','User::search');
$routes->post('/user/changePassword', 'User::changePassword');
$routes->post('/user/update', 'User::update');
$routes->post('/user/updateAvatar', 'User::updateAvatar');
$routes->get('/logout', 'User::logout');
$routes->cli('server/start', 'Server::start');

/*
 * --------------------------------------------------------------------
 * Additional Routing
 * --------------------------------------------------------------------
 *
 * There will often be times that you need additional routing and you
 * need it to be able to override any defaults in this file. Environment
 * based routes is one such time. require() additional route files here
 * to make that happen.
 *
 * You will have access to the $routes object within that file without
 * needing to reload it.
 */
if (is_file(APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php')) {
    require APPPATH . 'Config/' . ENVIRONMENT . '/Routes.php';
}

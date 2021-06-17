<?php
require_once dirname(__FILE__) . '/libs/functions.php';
require_once dirname(__FILE__) . '/libs/database.php';

require 'vendor/autoload.php';

use Buki\Router\Router;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/// https://github.com/izniburak/php-router/wiki

$router = new Router;

$router->get('/', function(Request $request, Response $response) {
    $response->setContent('Access denied');
    return $response;
});

function zabezpiecz($zmienna)
{
    $zmienna = trim($zmienna);
    $zmienna = strip_tags($zmienna);

    return $zmienna;
}

$router->post('/addSell', function() {

    $db = new Database();

    if (isset($_POST['data']))
    {
        $data = str_replace("'", '"', $_POST['data']);
        $data = @json_decode($data, 1);
    }

    $cena = null;
    $zabezpieczone = [];

    if (isset($data['id']))
    {
        // Pobieram cenę produktu po ID
        $results = $db->query('SELECT cena FROM produkty WHERE id ='.$data['id']);

        if (isset($results[0]['cena']))
        {
            $cena = $results[0]['cena'];
            $zabezpieczone['cena'] = $cena;
        }
    }

    $_POST = $data;


    $bledy = [];

    if (isset($_POST['id']))
    {
        $zabezpieczone['produktId'] = zabezpiecz($_POST['id']);
    }

    $zabezpieczone['data_transakcji'] = date('Y-m-d H:i:s');

    if (isset($_POST['ilosc']))
    {
        $zabezpieczone['ilosc_sztuk'] = (int)$_POST['ilosc'];
    }

    if (count($bledy) > 0)
    {
        echo 'ERROR';
        die();
    }

    $result = $db->insert($zabezpieczone, 'sprzedaz');

    if ($result)
    {
        echo 'OK';
        die();
    }

    echo 'ERROR';
    die();
});

/** Pobieranie sprzedaży */
$router->get('/getSells', function() {

    $db = new Database();
    $results = $db->query('SELECT * FROM sprzedaz');

    return json_encode($results);

});

$router->post('/addProduct', function() {

    if (isset($_POST['data']))
    {
        $data = str_replace("'", '"', $_POST['data']);
        $data = @json_decode($data, 1);
    }

    $_POST = $data;


    $zabezpieczone = [];
    $bledy = [];

    if (isset($_POST['name']))
    {
        $zabezpieczone['produkt'] = zabezpiecz($_POST['name']);
    }

    if (isset($_POST['cena']))
    {
        $cena = (int)$_POST['cena'];

        if ($cena <= 0)
        {
            $bledy[] = 'Cena jest niepoprawna';
        }
        else
        {
            $zabezpieczone['cena'] = $_POST['cena'];

            if ((stripos($zabezpieczone['cena'], ',') !== false) || (stripos($zabezpieczone['cena'], '.') !== false))
            {
                $zabezpieczone['cena'] = str_replace(['.', ','], '', $zabezpieczone['cena']);
            }
            else
            {
                $zabezpieczone['cena'] = (int)$zabezpieczone['cena'] * 100;
            }
        }
    }

    if (isset($_POST['cena_promocyjna']))
    {
        $zabezpieczone['cena_promocyjna'] = $_POST['cena_promocyjna'];

        if ((stripos($zabezpieczone['cena_promocyjna'], ',') !== false) || (stripos($zabezpieczone['cena_promocyjna'], '.') !== false))
        {
            $zabezpieczone['cena_promocyjna'] = str_replace(['.', ','], '', $zabezpieczone['cena_promocyjna']);
        }
        else
        {
            $zabezpieczone['cena_promocyjna'] = (int)$zabezpieczone['cena_promocyjna'] * 100;
        }
    }

    if (isset($_POST['ilosc']))
    {
        $zabezpieczone['ilosc'] = (int)$_POST['ilosc'];
    }

    if (isset($_POST['opis']))
    {
        $zabezpieczone['opis'] = zabezpiecz($_POST['opis']);
    }

    if (count($bledy) > 0)
    {
        echo 'Error Błędy';
        die();
    }

    $db = new Database();
    $result = $db->insert($zabezpieczone, 'produkty');

    if ($result)
    {
        echo 'OK';
        die();
    }

    echo 'Error';
    die();
});

$router->get('/licence', function(Request $request, Response $response) {

    $db = new Database();

    $hash = 'KqV_f:#ry$';

    $code = date('YmdHis') . microtime() . $hash;

    $code = sha1($code);

    $save = [
        'klucz' => $code,
        'data_wygenerowania' => date('Y-m-d H:i:s'),
        'data_wygasniecia' => date('Y-m-d H:i:s', strtotime('+1 year')),
        'akceptacja' => 0
    ];

    $result = $db->insert($save, 'licencje');
    return $response;
});


$router->get('/licenceCheck/:string', function($code) {

    $db = new Database();

    $istnieje = $db->query('SELECT id FROM licencje WHERE klucz = "'.$code.'" and data_wygasniecia > "'.date('Y-m-d H:i:s').'"');

    if (isset($istnieje[0]))
    {
        echo 'OK';
        die();
    }

    echo 'Error';
    die();

});

$router->get('/get', function(Request $request, Response $response) {
    $db = new Database();
    $results = $db->query('SELECT *, (SELECT SUM(ilosc_sztuk) FROM sprzedaz WHERE produktId = produkty.id) as ilosc_sprzedanych FROM produkty');

    foreach ($results as $key => $value)
    {
        $results[$key]['ilosc'] = $results[$key]['ilosc'] - $value['ilosc_sprzedanych'];
    }

    $json = json_encode($results);
    echo $json;
});

$router->run();

?>

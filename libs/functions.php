<?php

if (!function_exists('dump'))
{
    function dump($data)
    {
        echo '<pre>';

        if (is_array($data))
        {
            print_r($data);
        }
        else
        {
            echo $data;
        }

        echo '</pre>';
    }
}

?>

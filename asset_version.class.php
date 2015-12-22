<?php

use RocketSled\Runnable;

class AssetVersion implements Runnable
{

    private $template;
    private $time_stamp;
    private $server;
    private $path;

    public function __construct ()
    {
        $this->path = $_GET[ 'path' ];
    }

    public function run ()
    {
        $time_stamp = file_get_contents ( dirname ( __File__ ) . "/Timestamp.txt" );
        $_SESSION[ 'Time_stamp' ] = $time_stamp;
        $path = $this->path;
        @header ( "Content-type: text/css; charset: UTF-8" );
        $file = fopen ( $path , "r" );
        while ( ($line = fgets ( $file )) !== false )
        {
            if ( (strpos ( $line , 'url(' ) !== false ) )
            {
                $imagevalue = explode ( "(" , $line );
                $imagevalue = explode ( ")" , $imagevalue[ 1 ] );
                $path_parts = pathinfo ( $imagevalue[ 0 ] );
                if ( !(strpos ( $imagevalue[ 0 ] , 'fonts' ) !== false) )
                {
                    $value = explode ( "." . $path_parts[ "extension" ] , $imagevalue[ 0 ] );
                    $new_value = self::set_time_stamp ( $value , $path_parts[ 'extension' ] , $_SESSION[ 'Time_stamp' ] );
                    $line = str_replace ( $imagevalue[ 0 ] , $new_value , $line );
                }
                else
                {
                    echo $line;
                }
            }
            @header ( "Content-type: text/css; charset: UTF-8" );
            echo $line;
        }
        //        
        fclose ( $file );
    }

    public static function Init ()
    {
        $file = '/var/www/rentingsmart.me/public_html/AssetVersioning/Timestamp.txt';
        $time = time ();
        file_put_contents ( $file , $time );
        $_SESSION[ 'Time_stamp' ] = $time;
    }

    public static function Install ( $array = array( "css" , "js" , "png" , "jpg" ) , $server = "Nigix" )
    {

        if ( $server == "Apache" )
        {
            $file = '/var/www/rentingsmart.me/public_html/RentingSmartApp/.htaccess';
            $Rules = "\n<IfModule mod_rewrite.c>\n RewriteEngine on\n";
            file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
            foreach ( $array as $val )
            {
                $Rules = "RewriteRule   (([a-zA-Z0-9_]+[/])+)+([-.a-zA-Z0-9_]+)+_t_+([0-9]{10}+)." . $val . "$ http://%{HTTP_HOST}/$1$3." . $val . " [p]" . "\n";
                file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
            }
            $Rules = "RewriteRule   (([a-zA-Z0-9_]+[/])+)+([-.a-zA-Z0-9_]+)+_t_+([0-9]{10}+)." . $val . "$ http://rentingsmart.me/?r=AssetVersion&path=http://%{HTTP_HOST}/$1$3." . $val . " [p]" . "\n";
            file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
            $Rules = "</IfModule>\n";
            file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
        }
        if ( $server == "Nigix" )
        {
            $file = '/var/www/rentingsmart.me/public_html/RentingSmartApp/.htaccess';
            $Rules = "# nginx configuration\nlocation / {\n";
            file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
            foreach ( $array as $val )
            {
                $Rules = "rewrite " . '"(([a-zA-Z0-9_]+[/])+)+([-.a-zA-Z0-9_]+)+_t_+([0-9]{10}+).' . "css" . '$"' . "\nhttp://$http_host/$1$3." . "css" . " redirect;\n";
                file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
            }
            $Rules = "}\n";
            file_put_contents ( $file , $Rules , FILE_APPEND | LOCK_EX );
        }
    }

    public static function ChangeDocumentPath ( $arg , $template )
    {
        $size = sizeof ( $arg );
        $array = array( "gif" , "css" );
        self::Install ( $array , "Apache" );

        for ( $i = 0; $i < $size; $i++ )
        {
            $ext = $arg[ $i ];
            switch ( $ext )
            {
                case ".css":

                    $links = $template->query ( './/head/link/@href' );
                    break;

                case ".js":

                    $links = $template->query ( './/head/script/@src' );
                    break;

                case ".image":

                    $links = $template->query ( './/img/@src' );
                    break;
            }

            foreach ( $links as $link )
            {
                $path_parts = pathinfo ( $link->nodeValue );
                $value = explode ( "." . $path_parts[ "extension" ] , $link->nodeValue );
                $external_url = $link->nodeValue;
                $internal_url = $_SERVER[ 'HTTP_HOST' ];
                $url_host = parse_url ( $external_url , PHP_URL_HOST );
                $base_url_host = $internal_url;
                if ( ($url_host == $base_url_host || empty ( $url_host )) && isset ( $path_parts[ 'extension' ] ) )
                {
                    $new_value = self::set_time_stamp ( $value , $path_parts[ 'extension' ] , $_SESSION[ 'Time_stamp' ] );
                    $link->nodeValue = $new_value;
                }
            }
        }
        return $template;
    }

    public static function set_time_stamp ( $value , $extention , $time_stamp )
    {
        return $value[ 0 ] . "_t_" . $time_stamp . "." . $extention;
    }

}

<?php

namespace App\Services\Megaplan;

use Exception;

//{{{ SdfApi_RequestInfo
/**
 * Объект-контейнер параметров запроса
 * 
 * @since 01.04.2010 12:25:00
 * @author megaplan
 */
class MegaplanRequestInfo
{
    /** Список параметров @var array */
    protected $params;
    /** Список поддерживаемых HTTP-методов @var array */
    protected static $supportingMethods = array('GET', 'POST', 'PUT', 'DELETE');
    /** Список принимаемых HTTP-заголовков @var array */
    protected static $acceptedHeaders = array('Date', 'Content-Type', 'Content-MD5', 'Post-Fields');
    //-----------------------------------------------------------------------------

    //{{{ create
    /**
     * Создает и возвращает объект
     * @since 01.04.2010 13:46
     * @author megaplan
     * @param string $method Метод запроса
     * @param string $host Хост мегаплана
     * @param string $uri URI запроса
     * @param array $headers Заголовки запроса
     * @return MegaplanRequestInfo
     */
    public static function create($method, $host, $uri, array $headers)
    {
        $method = mb_strtoupper($method);
        if (! in_array($method, self::$supportingMethods)) {
            throw new Exception("Unsupporting HTTP-Method '$method'");
        }

        $params = array(
            'Method' => $method,
            'Host' => $host,
            'Uri' => $uri
        );

        // фильтруем заголовки
        $validHeaders = array_intersect_key($headers, array_flip(self::$acceptedHeaders));
        $params = array_merge($params, $validHeaders);

        $request = new self($params);

        return $request;
    }
    //===========================================================================}}}
    //{{{ __construct
    /**
     * Создает объект
     * @since 01.04.2010 13:59
     * @author megaplan
     * @param array $params Параметры запроса
     */
    protected function __construct(array $params)
    {
        $this->params = $params;
    }
    //===========================================================================}}}
    //{{{ __get
    /**
     * Возвращает параметры запроса
     * @since 01.04.2010 14:26
     * @author megaplan
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        $name = preg_replace("/([a-z]{1})([A-Z]{1})/u", '$1-$2', $name);

        if (! empty($this->params[$name])) {
            return $this->params[$name];
        } else {
            return '';
        }
    }
    //===========================================================================}}}
}

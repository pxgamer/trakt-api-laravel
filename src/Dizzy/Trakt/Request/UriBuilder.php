<?php
/**
 * Created by PhpStorm.
 * User: Dizzy
 * Date: 14-7-2016
 * Time: 13:33
 *
 * Based on Wubs\Trakt\Request\UriBuilder
 */

namespace Dizzy\Trakt\Request;
use Dizzy\Trakt\Exceptions\MalformedParameterException;

/**
 * Class UriBuilder
 * @package Dizzy\Trakt\Request
 * @subpackage Wubs\Trakt\Request
 */
class UriBuilder
{
    /**
     * @var AbstractRequest
     */
    private $request;

    /**
     * UriBuilder constructor.
     * @param AbstractRequest $request
     */
    public function __construct(AbstractRequest $request)
    {
        $this->request = $request;
    }

    /**
     * Formats the uri for a Request object. Parts of the uri that start with an
     * ":" are the parameters of the uri. This method initiates the formatting process
     * for such an uri by getting the parameters first, and with the name of a parameter,
     * i'ts value can be retrieved. The value is either stored inside a public property of
     * the request object. Or it can be retrieved by a getter.
     *
     * @param AbstractRequest $request
     * @return string
     */
    public static function format(AbstractRequest $request)
    {
        $builder = new static($request);

        $keys = $builder->getParametersInUri();

        $values = $builder->getValuesFromUriParameters($keys);
        return $builder->formatUri($keys, $values);
    }

    /**
     * Gets all parameters inside the uri as an array.
     *
     * @return array
     */
    private function getParametersInUri()
    {
        $uri = $this->request->getUri();

        $parts = array_map(function ($part) { return ':' . $part; }, explode("/:", $uri));

        unset($parts[0]); //remove the base uri

        return array_map(function ($part) { return $this->stripTrailingPart($part); }, $parts);
    }

    /**
     * Gets the values for all the parameters that are retrieved by getParametersInUri.
     * When all the parameters are retrieved, it returns an associative array of [parameter => value]
     * If one parameter fails to be retrieved, it results in a MalformedParameterException.
     *
     * @param array $parameters
     * @return array
     * @throws MalformedParameterException
     */
    private function getValuesFromUriParameters(array $parameters)
    {
        return array_map(function ($parameter) {
            return $this->getValueFromParameter($parameter);
        }, $parameters);
    }

    /**
     * Get the value for the parameter that is prefixed with a ":" inside the url.
     * It can be retrieved by a getter (get+ParameterName) or by assigning the value
     * to a public property with the same name as the url parameter.
     *
     * $url = "users/:username/history"
     * becomes "user/itsdizzy/history" when $request::username is set to "itsdizzy"
     *
     * @param $parameter
     * @return mixed
     * @throws MalformedParameterException
     */
    private function getValueFromParameter($parameter)
    {
        $getter = $this->getValueGetter($parameter);
        if (method_exists($this->request, $getter)) {
            return $this->request->{$getter}();
        }

        if (property_exists($this->request, $parameter)) {
            return $this->request->{$parameter};
        }

        throw new MalformedParameterException;
    }

    /**
     * Formats the uri, it replaces the parameters with the values it has retrieved from
     * the request object.
     *
     * @param $keys
     * @param $values
     * @return mixed
     */
    private function formatUri($keys, $values)
    {
        return str_replace($keys, $values, $this->request->getUri());
    }

    /**
     * @param string $parameter
     * @return string
     */
    private function toCamelCase($parameter)
    {
        return ucwords(str_replace(['_', ' ', ':',], '', $parameter));
    }

    /**
     * @param string $parameter
     * @return string
     */
    private function getValueGetter($parameter)
    {
        return "get" . $this->toCamelCase($parameter);
    }

    /**
     * @param $parameter
     * @return string
     */
    private function stripTrailingPart($parameter)
    {
        if (!strstr($parameter, "/"))
            return $parameter;

        $pos = strpos($parameter, "/");
        return substr($parameter, 0, $pos);
    }
}
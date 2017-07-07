<?php

/**
 * Created by PhpStorm.
 * User: mac
 * Date: 12/06/2017
 * Time: 10:33
 */

class JString implements \ArrayAccess, \Iterator, Countable
{


    /**
     * @var array
     */
    private $internalArrayRepresentation;

    /**
     * @var int
     */
    private $internalCursor = 0;

    /**
     * @var bool
     */
    private $useStrict = true;

    private $encoding;

    /**
     * JsString constructor.
     * @param mixed $data
     * @param null $encoding
     */
    function __construct($data = '', $encoding = null)
    {
        if(!extension_loaded('mbstring'))
        {
            throw new RuntimeException("mbstring module not enabled");
        }

        /**
         * Check is encoding is supported and set internal encoding to it
         * else throw RuntimeException
         */

        if(!$encoding)
        {
            $this->encoding = mb_internal_encoding();
        }
        else if ('UTF-8' === $encoding || false !== @iconv($encoding, $encoding, ' '))
        {
            mb_internal_encoding($encoding);
            $this->encoding = $encoding;
        }
        else{
            throw new RuntimeException("character encoding not supported");
        }

        $this->polyfillMissingMultibyteStringFunction();

        $string = self::checkAndGetString($data);
        $this->internalArrayRepresentation = $this->strToArray($string);
    }

    private function polyfillMissingMultibyteStringFunction()
    {
        if (!function_exists('mb_ord')) {
            function mb_ord($char, $encoding = 'UTF-8') {
                if ($encoding === 'UCS-4BE') {
                    list(, $ord) = (strlen($char) === 4) ? @unpack('N', $char) : @unpack('n', $char);
                    return $ord;
                } else {
                    return mb_ord(mb_convert_encoding($char, 'UCS-4BE', $encoding), 'UCS-4BE');
                }
            }
        }
    }


    /**
     * @param $string
     * @return array
     */
    private function strToArray($string)
    {
        return preg_split('//u', $string, null,PREG_SPLIT_NO_EMPTY);
    }



    public function __get($name)
    {
        if($name === 'length')
        {
            return mb_strlen($this->toString());
        }

        return null;
    }

    /**
     * @return string
     */
    public  function __toString ()
    {
        return implode("",$this->internalArrayRepresentation);
    }

    /**
     * @return JString
     */
    public function __clone()
    {
        return new JString($this);
    }


    /**
     * @param array $into
     * @param array $source
     */
    private function arrayMerge(array &$into, $source)
    {
        for($i = 0; $i < count($source); $i++)
        {
            $into[] = $source[$i];
        }
    }


    /**
     * @param $offset
     * @return string
     */
    private function get($offset)
    {
        $this->checkOffset($offset);
        return mb_substr($this->toString(), $offset, 1);
    }

    /**
     * @param $offset
     * @param mixed
     */
    private function set($offset, $value)
    {
        $this->checkOffset($offset);
        $string = self::checkAndGetString($value);
        $char = mb_substr($string, 0, 1);

        $this->internalArrayRepresentation[$offset] = $char;
    }

    private function checkOffset($offset)
    {
        $valid = $this->offsetExists($offset);
        if(!$valid)
        {
            if($this->useStrict)
            {
                throw new OutOfBoundsException("Given offset not exist size = {$this->length()} , index = {$offset}");
            }
        }
    }




    /**
     * @return string
     * @param $data
     * @throws InvalidArgumentException
     */
    private static function checkAndGetString($data)
    {

        if(is_string($data))
        {
            return $data;
        }
        else if(is_numeric($data))
        {
            return $data . '';
        }
        else if(empty($data))
        {
            return '';
        }
        else
        {
            if(is_object($data))
            {
                $rc = new ReflectionClass($data);
                if(!$rc->hasMethod('__toString'))
                {
                    goto error;
                }
                else{
                    return call_user_func([$data, '__toString']);
                }
            }
            else{
                error:
                    throw new InvalidArgumentException("Expected object(with __toString method) or string  or null or  ".__CLASS__." : ".gettype($data)." given");
            }

        }
    }



    /******** JS implementation *******/


    /**
     * @return int
     */
    public function length()
    {
        return count($this->internalArrayRepresentation);
    }

    /**
     * @return string
     */
    public  function toString ()
    {
        return $this->__toString();
    }

    /**
     * @return JString
     */
    public function copy()
    {
        return new JString($this->__toString());
    }

    /**
     * @param mixed
     * @return JString
     * @throws InvalidArgumentException
     */
    public function append($data)
    {
        $willBeAdd = self::checkAndGetString($data);

        $strArray = $this->strToArray($willBeAdd);
        $this->arrayMerge($this->internalArrayRepresentation, $strArray);

        return $this;
    }


    /**
     * @param array ...$charCodes
     * @return JString
     */
    public static function fromCharCode(...$charCodes)
    {
        $str = '';
        foreach ($charCodes as $charCode)
        {
            $str .= mb_convert_encoding('&#' . intval($charCode) . ';', 'UTF-8', 'HTML-ENTITIES');
        }

        return new JString($str);
    }



    /**
     * @param string $name
     * @return string string
     */
    public function anchor($name)
    {
        $jName = self::checkAndGetString($name);
        return sprintf("<a name='%s'>%s</a>", $jName, $this->toString());
    }





    /**
     * @param int $index
     * @return string
     */
    public function charAt($index = 0)
    {
        return $this->get($index);
    }


    public function setCharAt($index = 0, $char)
    {
        $this->set($index , $char);
    }

    /**
     * @param int $index
     * @return int
     */
    public function charCodeAt($index = 0)
    {
        return mb_ord($this->get($index));
    }

    /**
     * @param JString|string|mixed $data
     * @return JString
     */
    public function concat($data)
    {
        return (new JString($this))->append($data);
    }

    public function startsWith($haystack, $needle)
    {
        $length = mb_strlen($needle);
        return (mb_substr($haystack, 0, $length) === $needle);
    }

    /**
     * @param JString|string|mixed $search
     * @param int $position
     * @return bool
     */
    function endsWith($search, $position = -1)
    {
        if($position < 0 || $position > $this->length())
        {
            $length = $this->length();
        }
        else
        {
            $length = $position;
        }

        if ($length == 0) {
            return true;
        }

        $haystack = self::checkAndGetString($search);

        for($i = mb_strlen($haystack) - 1, $j = $length - 1; $i >= 0; $i--, $j--)
        {
            if(mb_substr($haystack, $i, 1) !== $this->get($j))
            {
                return false;
            }
        }

        return true;
    }

    /**
     * @param JString|string|mixed $search
     * @param int $position
     * @return bool|int
     */
    public function includes($search, $position = 0)
    {
        $this->checkOffset($position);
        return mb_strpos($this->toString(), self::checkAndGetString($search), $position) !== FALSE;
    }

    /**
     * @param $search
     * @param int $start
     * @return bool|int
     */
    public function indexOf($search, $start = 0)
    {
        $this->checkOffset($start);
        $index = mb_strpos($this->toString(), self::checkAndGetString($search), $start);
        return $index !== FALSE ? $index : -1;
    }

    /**
     * @param $search
     * @param int $start
     * @return bool|int
     */
    public function lastIndexOf($search, $start = 0)
    {
        $this->checkOffset($start);
        $index = mb_strrpos($this->toString(), self::checkAndGetString($search), $start);
        return $index !== FALSE ? $index : -1;
    }

    /**
     * @param string $url
     * @return string string
     */
    public function link($url)
    {
        $url = self::checkAndGetString($url);
        return sprintf("<a href='%s'>%s</a>", $url, $this->toString());
    }


    /**
     * @param string|JString|object $compareString
     * @return int
     */
    public function compare($compareString)
    {
        $compareString = new JString(self::checkAndGetString($compareString));
        for($i = 0; $i < $this->length() && $i < $compareString->length(); $i++)
        {
            if($this->charCodeAt($i) !== $compareString->charCodeAt($i))
            {
                return $this->charCodeAt($i) < $compareString->charCodeAt($i) ? -1 : 1;
            }

        }

        return $this->length() < $compareString->length() ? -1 : $this->length() == $compareString->length() ? 0 : 1;
    }

    /**
     * @param string|JString|object $compareString
     * @return bool
     */
    public function isEqual($compareString)
    {
        return $this->compare($compareString) === 0;
    }

    /**
     * @return JString
     */
    public function capitalize($compareString)
    {

    }


    /**
     * @param JString $compareString
     * @return array
     */
    public function match($pattern)
    {
        $pattern = self::checkAndGetString($pattern);
        $match = array();

        $_ = mb_ereg ($pattern , $this->toString(), $match );

        return $match;
    }

    /**
     * @param JString $pattern
     * @return bool
     */
    public function test($pattern)
    {
        $pattern = self::checkAndGetString($pattern);

        return mb_ereg_match ($pattern , $this->toString());
    }

    /**
     * @param $count
     * @return JString
     */
    public function repeat($count = 0)
    {
        if((int)$count < 0 || ! is_numeric($count) || !is_finite($count))
        {
            throw new RangeException("count would be positive number");
        }

        $repeat = new JString();
        for($i = 0; $i < (int) $count; $i++)
        {
            $repeat->append($this);
        }

        return $repeat;
    }





    /******** \Iterator,\ArrayAccess *******/


    /**
     * Whether a offset exists
     * @link http://php.net/manual/en/arrayaccess.offsetexists.php
     * @param mixed $offset <p>
     * An offset to check for.
     * </p>
     * @return boolean true on success or false on failure.
     * </p>
     * <p>
     * The return value will be casted to boolean if non-boolean was returned.
     * @since 5.0.0
     */
    public function offsetExists($offset)
    {
        return $offset >= 0 && $this->length() > $offset;
    }

    /**
     * Offset to retrieve
     * @link http://php.net/manual/en/arrayaccess.offsetget.php
     * @param mixed $offset <p>
     * The offset to retrieve.
     * </p>
     * @return mixed Can return all value types.
     * @since 5.0.0
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Offset to set
     * @link http://php.net/manual/en/arrayaccess.offsetset.php
     * @param mixed $offset <p>
     * The offset to assign the value to.
     * </p>
     * @param mixed $value <p>
     * The value to set.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Offset to unset
     * @link http://php.net/manual/en/arrayaccess.offsetunset.php
     * @param mixed $offset <p>
     * The offset to unset.
     * </p>
     * @return void
     * @since 5.0.0
     */
    public function offsetUnset($offset)
    {
        //
    }

    /**
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     * @since 5.0.0
     */
    public function current()
    {
        return $this[$this->internalCursor];
    }

    /**
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function next()
    {
        $this->internalCursor++;
    }

    /**
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     * @since 5.0.0
     */
    public function key()
    {
        return $this->internalCursor;
    }

    /**
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     * Returns true on success or false on failure.
     * @since 5.0.0
     */
    public function valid()
    {
        return $this->offsetExists($this->internalCursor);
    }

    /**
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     * @since 5.0.0
     */
    public function rewind()
    {
        $this->internalCursor = 0;
    }

    /**
     * Count elements of an object
     * @link http://php.net/manual/en/countable.count.php
     * @return int The custom count as an integer.
     * </p>
     * <p>
     * The return value is cast to an integer.
     * @since 5.1.0
     */
    public function count()
    {
        return $this->length();
    }
}


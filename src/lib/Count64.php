<?php
/**
 * Simple class to support some very basic operations on 64 bit intergers
 * on 32 bit machines.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nicolai Ehemann <en@enlightened.de>
 * @copyright Copyright (C) 2013-2014 Nicolai Ehemann and contributors
 * @license GNU GPL
 */
namespace ZipStreamer;

const INT64_HIGH_MAP = 0xffffffff00000000;
const INT64_LOW_MAP =  0x00000000ffffffff;

/**
 * Pack 2 byte data into binary string, little endian format
 *
 * @param mixed $data data
 * @return string 2 byte binary string
 */
function pack16le($data) {
  return pack('v', $data);
}

/**
 * Pack 4 byte data into binary string, little endian format
 *
 * @param mixed $data data
 * @return 4 byte binary string
 */
function pack32le($data) {
  return pack('V', $data);
}

/**
 * Pack 8 byte data into binary string, little endian format
 *
 * @param mixed $data data
 * @return string 8 byte binary string
 */
function pack64le($data) {
  if (is_object($data)) {
    if ("Count64_32" == get_class($data)) {
      $value = $data->_getValue();
      $hiBytess = $value[0];
      $loBytess = $value[1];
    } else {
      $hiBytess = ($data->_getValue() & INT64_HIGH_MAP) >> 32;
      $loBytess = $data->_getValue() & INT64_LOW_MAP;
    }
  } else {
    $hiBytess = ($data & INT64_HIGH_MAP) >> 32;
    $loBytess = $data & INT64_LOW_MAP;
  }
  return pack('VV', $loBytess, $hiBytess);
}


abstract class Count64Base {
  function __construct($value = 0) {
    $this->set($value);
  }
  abstract public function set($value);
  abstract public function add($value);
  abstract public function _getValue();

  const EXCEPTION_SET_INVALID_ARGUMENT = "Count64 object can only be set() to integer or Count64 values";
  const EXCEPTION_ADD_INVALID_ARGUMENT = "Count64 object can only be add()ed integer or Count64 values";
}

class Count64_32 extends Count64Base{
  private $loBytes;
  private $hiBytes;

  public function _getValue() {
    return array($this->hiBytes, $this->loBytes);
  }

  public function set($value) {
    if (is_int($value)) {
      $this->loBytes = $value;
      $this->hiBytes = 0;
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $value = $value->_getValue();
      $this->hiBytes = $value[0];
      $this->loBytes = $value[1];
    } else {
      throw Exception(self::EXCEPTION_SET_INVALID_ARGUMENT);
    }
    return $this;
  }

  public function add($value) {
    if (is_int($value)) {
      $sum = (int)($this->loBytes + $value);
      // overflow!
      if (($this->loBytes > -1 && $sum < $this->loBytes && $sum > -1)
      || ($this->loBytes < 0 && ($sum < $this->loBytes || $sum > -1))) {
        $this->hiBytes = (int)($this->hiBytes + 1);
      }
      $this->loBytes = $sum;
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $value = $value->_getValue();
      $sum = (int)($this->loBytes + $value[1]);
      if (($this->loBytes > -1 && $sum < $this->loBytes && $sum > -1)
      || ($this->loBytes < 0 && ($sum < $this->loBytes || $sum > -1))) {
        $this->hiBytes = (int)($this->hiBytes + 1);
      }
      $this->loBytes = $sum;
      $this->hiBytes = (int)($this->hiBytes + $value[0]);
    } else {
      throw Exception(self::EXCEPTION_ADD_INVALID_ARGUMENT);
    }
    return $this;
  }
}

class Count64_64 extends Count64Base {
  private $value;

  public function _getValue() {
    return $this->value;
  }

  public function set($value) {
    if (is_int($value)) {
      $this->value = $value;
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $this->value = $value->_getValue();
    } else {
      throw Exception(self::EXCEPTION_SET_INVALID_ARGUMENT);
    }
    return $this;
  }

  public function add($value) {
    if (is_int($value)) {
      $this->value = (int)($this->value + $value);
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $this->value = (int)($this->value + $value->_getValue());
    } else {
      throw Exception(self::EXCEPTION_ADD_INVALID_ARGUMENT);
    }
    return $this;
  }
}

abstract class Count64  {
  public static function construct($value = 0) {
    if (4 == PHP_INT_SIZE) {
      return new Count64_32($value);
    } else {
      return new Count64_64($value);
    }
  }
}

?>
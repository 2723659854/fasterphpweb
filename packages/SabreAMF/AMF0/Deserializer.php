<?php

    require_once dirname(__FILE__) . '/../AMF0/Const.php';
    require_once dirname(__FILE__) . '/../Const.php';
    require_once dirname(__FILE__) . '/../Deserializer.php';
    require_once dirname(__FILE__) . '/../AMF3/Deserializer.php';
    require_once dirname(__FILE__) . '/../AMF3/Wrapper.php';
    require_once dirname(__FILE__) . '/../TypedObject.php';

    /**
     * 这个是读取amf数据的操作，反序列化
     * SabreAMF_AMF0_Deserializer
     *
     * @package SabreAMF
     * @subpackage AMF0
     * @version $Id: Deserializer.php 233 2009-06-27 23:10:34Z evertpot $
     * @copyright Copyright (C) 2006-2009 Rooftop Solutions. All rights reserved.
     * @author Evert Pot (http://www.rooftopsolutions.nl/)
     * @licence http://www.freebsd.org/copyright/license.html  BSD License (4 Clause)
     * @uses SabreAMF_Const
     * @uses SabreAMF_AMF0_Const
     * @uses SabreAMF_AMF3_Deserializer
     * @uses SabreAMF_AMF3_Wrapper
     * @uses SabreAMF_TypedObject
     */
    class SabreAMF_AMF0_Deserializer extends SabreAMF_Deserializer {

        /**
         * refList
         *
         * @var array
         */
        private $refList = array();

        /**
         * amf3Deserializer
         *
         * @var SabreAMF_AMF3_Deserializer
         */
        private $amf3Deserializer = null;

        /**
         * 读取数据
         * readAMFData
         *
         * @param int $settype
         * @param bool $newscope
         * @return mixed
         */
        public function readAMFData($settype = null,$newscope = false) {

           if ($newscope) $this->refList = array();

           if (is_null($settype)) {
                $settype = $this->stream->readByte();
           }

           /** 根据流媒体类型确定读取方式 */
           switch ($settype) {

                case SabreAMF_AMF0_Const::DT_NUMBER      : return $this->stream->readDouble();
                case SabreAMF_AMF0_Const::DT_BOOL        : return $this->stream->readByte()==true;
                case SabreAMF_AMF0_Const::DT_STRING      : return $this->readString();
                case SabreAMF_AMF0_Const::DT_OBJECT      : return $this->readObject();
                case SabreAMF_AMF0_Const::DT_NULL        : return null;
                case SabreAMF_AMF0_Const::DT_UNDEFINED   : return null;
                case SabreAMF_AMF0_Const::DT_REFERENCE   : return $this->readReference();
                case SabreAMF_AMF0_Const::DT_MIXEDARRAY  : return $this->readMixedArray();
                case SabreAMF_AMF0_Const::DT_ARRAY       : return $this->readArray();
                case SabreAMF_AMF0_Const::DT_DATE        : return $this->readDate();
                case SabreAMF_AMF0_Const::DT_LONGSTRING  : return $this->readLongString();
                case SabreAMF_AMF0_Const::DT_UNSUPPORTED : return null;
                case SabreAMF_AMF0_Const::DT_XML         : return $this->readLongString();
                case SabreAMF_AMF0_Const::DT_TYPEDOBJECT : return $this->readTypedObject();
                case SabreAMF_AMF0_Const::DT_AMF3        : return $this->readAMF3Data();
                default                   :  throw new Exception('Unsupported type: 0x' . strtoupper(str_pad(dechex($settype),2,0,STR_PAD_LEFT))); return false;

           }

        }

        /**
         * 读取对象
         * readObject
         *
         * @return object
         */
        public function readObject() {

            $object = array();
            /** 这里使用了地址引用 */
            $this->refList[] =& $object;
            while (true) {
                /** 读取字符串 */
                $key = $this->readString();
                /** 读取数据流的字节 */
                $vartype = $this->stream->readByte();
                /** 如果遇到结束标记则跳出循环 */
                if ($vartype==SabreAMF_AMF0_Const::DT_OBJECTTERM) break;
                /** 读取指定类型的数据 */
                $object[$key] = $this->readAmfData($vartype);
            }
            if (defined('SABREAMF_OBJECT_AS_ARRAY')) {
                $object = (object)$object;
            }
            return $object;

        }

        /**
         * 从地址中读取数据
         * readReference
         *
         * @return object
         */
        public function readReference() {

            $refId = $this->stream->readInt();
            if (isset($this->refList[$refId])) {
                return $this->refList[$refId];
            } else {
                throw new Exception('Invalid reference offset: ' . $refId);
                return false;
            }

        }


        /**
         * 读取数组
         * readArray
         *
         * @return array
         */
        public function readArray() {
            /** 读取长度 */
            $length = $this->stream->readLong();
            $arr = array();
            $this->refList[]&=$arr;
            while($length--) $arr[] = $this->readAMFData();
            return $arr;

        }

        /**
         * 读取混合类型数组
         * readMixedArray
         *
         * @return array
         */
        public function readMixedArray() {

            $highestIndex = $this->stream->readLong();
            return $this->readObject();

        }

       /**
         * 读取指定长度缓冲池
         * readString
         *
         * @return string
         */
        public function readString() {

            $strLen = $this->stream->readInt();
            return $this->stream->readBuffer($strLen);

        }

        /**
         * 读取超长缓冲数据
         * readLongString
         *
         * @return string
         */
        public function readLongString() {

            $strLen = $this->stream->readLong();
            return $this->stream->readBuffer($strLen);

        }

        /**
         * 读取日期
         * readDate
         *
         * @return int
         */
        public function readDate() {

            // Unix timestamp in seconds. We strip the millisecond part
            $timestamp = floor($this->stream->readDouble() / 1000);

            // we are ignoring the timezone
            $timezoneOffset = $this->stream->readInt();
            //if ($timezoneOffset > 720) $timezoneOffset = ((65536 - $timezoneOffset));
            //$timezoneOffset=($timezoneOffset * 60) - date('Z');

            $dateTime = new DateTime('@' . $timestamp);

            return $dateTime;

        }

        /**
         * 读取对象
         * readTypedObject
         *
         * @return object
         */
        public function readTypedObject() {

            $classname = $this->readString();

            $isMapped = false;

            if ($classname = $this->getLocalClassName($classname)) {
                $rObject = new $classname();
                $isMapped = true;
            } else {
                $rObject = new SabreAMF_TypedObject($classname,null);
            }
            $this->refList[] =& $rObject;

            $props = array();
            while (true) {
                $key = $this->readString();
                $vartype = $this->stream->readByte();
                if ($vartype==SabreAMF_AMF0_Const::DT_OBJECTTERM) break;
                $props[$key] = $this->readAmfData($vartype);
            }

            if ($isMapped) {
                foreach($props as $k=>$v)
                    $rObject->$k = $v;
            } else {
                $rObject->setAMFData($props);
            }

            return $rObject;

        }

        /**
         * 读取amf3的数据
         * readAMF3Data
         *
         * @return SabreAMF_AMF3_Wrapper
         */
        public function readAMF3Data() {

            $amf3Deserializer = new SabreAMF_AMF3_Deserializer($this->stream);
            return new SabreAMF_AMF3_Wrapper($amf3Deserializer->readAMFData());

        }


   }



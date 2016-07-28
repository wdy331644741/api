<?php
namespace Lib;
class Xxtea
{
    const PC_NUM = '7004';
    const H5_NUM = '7005';
    const IOS_NUM = '7006';
    const ANDROID_NUM = '7007';
    static $keys = [
        self::PC_NUM => 'wlPC*PC;pc!Pc',
        self::H5_NUM => 'wlh5_H5~h5#H5',
        self::IOS_NUM => 'wlios(IOSios}Ios',
        self::ANDROID_NUM => 'wlAZ+az\'Az',
    ];

    static public function de($sourceStr){
        if($sourceStr[0] !== '{')
        {
            $num = substr($sourceStr,0,4 );
            $str = substr($sourceStr,4);
            $xxtea = new self();
            return $xxtea->decrypt($str,self::$keys[$num]);
        }
        else
        {
            return $sourceStr;
        }
    }

    public function encrypt($s, $key)
    {
        $src = array("/", "+", "=");
        $dist = array("_a", "_b", "_c");
        $old = base64_encode(self::xxtea_encrypt($s, $key));
        $new = str_replace($src, $dist, $old);
        return $new;
    }

    public function decrypt($e, $key)
    {
        $src = array("_a", "_b", "_c");
        $dist = array("/", "+", "=");
        $old = str_replace($src, $dist, $e);
        $new = self::xxtea_decrypt(base64_decode($old), $key);
        return $new;
    }

    private function long2str($v, $w)
    {

        $len = count($v);
        $n = ($len - 1) << 2;
        if ($w) {
            $m = $v[$len - 1];
            if (($m < $n - 3) || ($m > $n)) {
                return false;
            }

            $n = $m;
        }
        $s = array();
        for ($i = 0; $i < $len; $i++) {
            $s[$i] = pack("V", $v[$i]);
        }
        if ($w) {
            return substr(join('', $s), 0, $n);
        } else {
            return join('', $s);
        }
    }

    private function str2long($s, $w)
    {

        $v = unpack("V*", $s . str_repeat("\0", (4 - strlen($s) % 4) & 3));
        $v = array_values($v);
        if ($w) {
            $v[count($v)] = strlen($s);
        }
        return $v;
    }

    private function int32($n)
    {

        while ($n >= 2147483648) {
            $n -= 4294967296;
        }

        while ($n <= -2147483649) {
            $n += 4294967296;
        }

        return (int)$n;
    }

    private function xxtea_encrypt($str, $key)
    {

        if ($str == "") {
            return "";
        }
        $v = self::str2long($str, true);
        $k = self::str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        $n = count($v) - 1;
        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = 0;

        while (0 < $q--) {
            $sum = self::int32($sum + $delta);
            $e = $sum >> 2 & 3;
            for ($p = 0; $p < $n; $p++) {
                $y = $v[$p + 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $z = $v[$p] = self::int32($v[$p] + $mx);
            }
            $y = $v[0];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $z = $v[$n] = self::int32($v[$n] + $mx);
        }
        return self::long2str($v, false);
    }

    private function xxtea_decrypt($str, $key)
    {
        if ($str == "") {
            return "";
        }
        $v = self::str2long($str, false);
        $k = self::str2long($key, false);
        if (count($k) < 4) {
            for ($i = count($k); $i < 4; $i++) {
                $k[$i] = 0;
            }
        }
        $n = count($v)
            - 1;

        $z = $v[$n];
        $y = $v[0];
        $delta = 0x9E3779B9;
        $q = floor(6 + 52 / ($n + 1));
        $sum = self::int32($q * $delta);

        while ($sum != 0) {
            $e = $sum >> 2 & 3;
            for ($p = $n; $p > 0; $p--) {
                $z = $v[$p - 1];
                $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
                $y = $v[$p] = self::int32($v[$p] - $mx);
            }
            $z = $v[$n];
            $mx = self::int32((($z >> 5 & 0x07ffffff) ^ $y << 2) + (($y >> 3 & 0x1fffffff) ^ $z << 4)) ^ self::int32(($sum ^ $y) + ($k[$p & 3 ^ $e] ^ $z));
            $y = $v[0] = self::int32($v[0] - $mx);
            $sum = self::int32($sum - $delta);
        }
        return self::long2str($v, true);
    }
}

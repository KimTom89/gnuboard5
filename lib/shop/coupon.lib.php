<?php
/**
 * 쿠폰번호 생성함수
 * @return string 쿠폰번호
 */
function get_coupon_id()
{
    $len    = 16;
    $chars  = "ABCDEFGHJKLMNPQRSTUVWXYZ123456789";

    srand((double)microtime() * 1000000);

    $i = 0;
    $str = '';

    while ($i < $len) {
        $num = rand() % strlen($chars);
        $tmp = substr($chars, $num, 1);
        $str .= $tmp;
        $i++;
    }

    $str = preg_replace("/([0-9A-Z]{4})([0-9A-Z]{4})([0-9A-Z]{4})([0-9A-Z]{4})/", "\\1-\\2-\\3-\\4", $str);

    return $str;
}

/**
 * 쿠폰 사용여부 확인
 * @param string $mb_id     회원ID
 * @param string $cp_id     쿠폰코드
 * @return boolean
 */
function is_used_coupon($mb_id, $cp_id)
{
    global $g5;

    $row = sql_fetch("SELECT EXISTS (SELECT 1 FROM {$g5['g5_shop_coupon_log_table']} WHERE mb_id = '{$mb_id}' AND cp_id = '{$cp_id}') AS exist");
    if ($row['exist']) {
        return true;
    } else {
        return false;
    }
}

/**
 * 다운로드한 쿠폰인지 확인
 * @param string $mb_id     회원ID
 * @param string $cz_id     쿠폰존ID
 * @return boolean
 */
function is_coupon_downloaded($mb_id, $cz_id)
{
    global $g5;

    $row = sql_fetch(" SELECT EXISTS (SELECT 1 FROM {$g5['g5_shop_coupon_table']} WHERE mb_id = '$mb_id' AND cz_id = '$cz_id') AS exist ");
    if ($mb_id && $row['exist']) {
        return true;
    } else {
        return false;
    }
}

<?php
function message($subject, $content, $align="left", $width="450")
{
    $str = "
        <table width=\"$width\" cellpadding=\"4\" align=\"center\">
            <tr><td class=\"line\" height=\"1\"></td></tr>
            <tr>
                <td align=\"center\">$subject</td>
            </tr>
            <tr><td class=\"line\" height=\"1\"></td></tr>
            <tr>
                <td>
                    <table width=\"100%\" cellpadding=\"8\" cellspacing=\"0\">
                        <tr>
                            <td class=\"leading\" align=\"$align\">$content</td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr><td class=\"line\" height=\"1\"></td></tr>
        </table>
        <br>
        ";
    return $str;
}


// 시간이 비어 있는지 검사
function is_null_time($datetime)
{
    // 공란 0 : - 제거
    //$datetime = ereg_replace("[ 0:-]", "", $datetime); // 이 함수는 PHP 5.3.0 에서 배제되고 PHP 6.0 부터 사라집니다.
    $datetime = preg_replace("/[ 0:-]/", "", $datetime);
    if ($datetime == "")
        return true;
    else
        return false;
}




// 메일 보내는 내용을 HTML 형식으로 만든다.
function email_content($str)
{
    global $g5;

    $s = "";
    $s .= "<html><head><meta http-equiv=\"content-type\" content=\"text/html; charset={$g5['charset']}\"><title>메일</title>\n";
    $s .= "<body>\n";
    $s .= $str;
    $s .= "</body>\n";
    $s .= "</html>";

    return $s;
}


// 타임스탬프 형식으로 넘어와야 한다.
// 시작시간, 종료시간
function gap_time($begin_time, $end_time)
{
    $gap = $end_time - $begin_time;
    $time['days']    = (int)($gap / 86400);
    $time['hours']   = (int)(($gap - ($time['days'] * 86400)) / 3600);
    $time['minutes'] = (int)(($gap - ($time['days'] * 86400 + $time['hours'] * 3600)) / 60);
    $time['seconds'] = (int)($gap - ($time['days'] * 86400 + $time['hours'] * 3600 + $time['minutes'] * 60));
    return $time;
}


// 공란없이 이어지는 문자 자르기 (wayboard 참고 (way.co.kr))
function continue_cut_str($str, $len=80)
{
    /*
    $pattern = "[^ \n<>]{".$len."}";
    return eregi_replace($pattern, "\\0\n", $str);
    */
    $pattern = "/[^ \n<>]{".$len."}/";
    return preg_replace($pattern, "\\0\n", $str);
}


// 제목별로 컬럼 정렬하는 QUERY STRING
// $type 이 1이면 반대
function title_sort($col, $type=0)
{
    global $sort1, $sort2;
    global $_SERVER;
    global $page;
    global $doc;

    $q1 = 'sort1='.$col;
    if ($type) {
        $q2 = 'sort2=desc';
        if ($sort1 == $col) {
            if ($sort2 == 'desc') {
                $q2 = 'sort2=asc';
            }
        }
    } else {
        $q2 = 'sort2=asc';
        if ($sort1 == $col) {
            if ($sort2 == 'asc') {
                $q2 = 'sort2=desc';
            }
        }
    }
    #return "$_SERVER[SCRIPT_NAME]?$q1&amp;$q2&amp;page=$page";
    return "{$_SERVER['SCRIPT_NAME']}?$q1&amp;$q2&amp;page=$page";
}

// 세션값을 체크하여 이쪽에서 온것이 아니면 메인으로
function session_check()
{
    global $g5;

    if (!trim(get_session('ss_uniqid')))
        gotourl(G5_SHOP_URL);
}

// 일자형식변환
function date_conv($date, $case=1)
{
    if ($case == 1) { // 년-월-일 로 만들어줌
        $date = preg_replace("/([0-9]{4})([0-9]{2})([0-9]{2})/", "\\1-\\2-\\3", $date);
    } else if ($case == 2) { // 년월일 로 만들어줌
        $date = preg_replace("/-/", "", $date);
    }

    return $date;
}


function get_yn($val, $case='')
{
    switch ($case) {
        case '1' : $result = ($val > 0) ? 'Y' : 'N'; break;
        default :  $result = ($val > 0) ? '예' : '아니오';
    }
    return $result;
}



// 패턴의 내용대로 해당 디렉토리에서 정렬하여 <select> 태그에 적용할 수 있게 반환
function get_list_skin_options($pattern, $dirname='./', $sval='')
{
    $str = '<option value="">선택</option>'.PHP_EOL;

    unset($arr);
    $handle = opendir($dirname);
    while ($file = readdir($handle)) {
        if (preg_match("/$pattern/", $file, $matches)) {
            $arr[] = $matches[0];
        }
    }
    closedir($handle);

    sort($arr);
    foreach($arr as $value) {
        if($value == $sval)
            $selected = ' selected="selected"';
        else
            $selected = '';

        $str .= '<option value="'.$value.'"'.$selected.'>'.$value.'</option>'.PHP_EOL;
    }

    return $str;
}


// 일자 시간을 검사한다.
function check_datetime($datetime)
{
    if ($datetime == "0000-00-00 00:00:00")
        return true;

    $year   = substr($datetime, 0, 4);
    $month  = substr($datetime, 5, 2);
    $day    = substr($datetime, 8, 2);
    $hour   = substr($datetime, 11, 2);
    $minute = substr($datetime, 14, 2);
    $second = substr($datetime, 17, 2);

    $timestamp = mktime($hour, $minute, $second, $month, $day, $year);

    $tmp_datetime = date("Y-m-d H:i:s", $timestamp);
    if ($datetime == $tmp_datetime)
        return true;
    else
        return false;
}


// 경고메세지를 경고창으로
function alert_opener($msg='', $url='')
{
    global $g5;

    if (!$msg) $msg = '올바른 방법으로 이용해 주십시오.';

    echo "<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\">";
    echo "<script>";
    echo "alert(\"$msg\");";
    echo "opener.location.href=\"$url\";";
    echo "self.close();";
    echo "</script>";
    exit;
}


// option 리스트에 selected 추가
function conv_selected_option($options, $value)
{
    if(!$options)
        return '';

    $options = str_replace('value="'.$value.'"', 'value="'.$value.'" selected', $options);

    return $options;
}
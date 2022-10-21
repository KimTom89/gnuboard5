<?php
/**
 * 배너영역 출력
 * @param string $position  출력위치 (메인/왼쪽)
 * @param string $skin      스킨파일 이름
 * @return void
 */
function display_banner($position = '왼쪽', $skin = 'boxbanner.skin.php')
{
    global $g5;

    if (G5_IS_MOBILE) {
        $bn_device = 'mobile';
        $skin_path = G5_MSHOP_SKIN_PATH . '/' . $skin;
    } else {
        $bn_device = 'pc';
        $skin_path = G5_SHOP_SKIN_PATH . '/' . $skin;
    }

    if (file_exists($skin_path)) {
        // 배너 출력
        $sql = " SELECT * 
                FROM {$g5['g5_shop_banner_table']}
                WHERE '" . G5_TIME_YMDHIS . "' BETWEEN bn_begin_time AND bn_end_time
                AND ( bn_device = 'both' OR bn_device = '{$bn_device}')
                AND bn_position = '$position'
                ORDER BY bn_order, bn_id DESC ";
        $result = sql_query($sql);

        // include 파일 내부에서 $result 변수 사용
        include $skin_path;
    } else {
        echo '<p>'.str_replace(G5_PATH.'/', '', $skin_path).'파일이 존재하지 않습니다.</p>';
    }
}

<?php
// 별
function get_star($score)
{
    $star = round($score);
    if ($star > 5) $star = 5;
    else if ($star < 0) $star = 0;

    return $star;
}


// 별 이미지
function get_star_image($it_id)
{
    global $g5;

    $sql = "select (SUM(is_score) / COUNT(*)) as score from {$g5['g5_shop_item_use_table']} where it_id = '$it_id' and is_confirm = 1 ";
    $row = sql_fetch($sql);

    return (int)get_star($row['score']);
}

// 사용후기의 선호도(별) 평균을 상품테이블에 저장합니다.
function update_use_avg($it_id)
{
    global $g5;
    $row = sql_fetch(" select count(*) as cnt, sum(is_score) as total from {$g5['g5_shop_item_use_table']} where it_id = '{$it_id}' and is_confirm = 1 ");
    $average = ($row['total'] && $row['cnt']) ? $row['total'] / $row['cnt'] : 0;
    return sql_query(" update {$g5['g5_shop_item_table']} set it_use_avg = '$average' where it_id = '{$it_id}' ");
}
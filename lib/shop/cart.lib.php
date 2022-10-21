<?php

// cart id 설정
function set_cart_id($direct)
{
    global $g5, $default, $member;

    if ($direct) {
        $tmp_cart_id = get_session('ss_cart_direct');
        if(!$tmp_cart_id) {
            $tmp_cart_id = get_uniqid();
            set_session('ss_cart_direct', $tmp_cart_id);
        }
    } else {
        // 비회원장바구니 cart id 쿠키설정
        if($default['de_guest_cart_use']) {
            $tmp_cart_id = preg_replace('/[^a-z0-9_\-]/i', '', get_cookie('ck_guest_cart_id'));
            if($tmp_cart_id) {
                set_session('ss_cart_id', $tmp_cart_id);
                //set_cookie('ck_guest_cart_id', $tmp_cart_id, ($default['de_cart_keep_term'] * 86400));
            } else {
                $tmp_cart_id = get_uniqid();
                set_session('ss_cart_id', $tmp_cart_id);
                set_cookie('ck_guest_cart_id', $tmp_cart_id, ($default['de_cart_keep_term'] * 86400));
            }
        } else {
            $tmp_cart_id = get_session('ss_cart_id');
            if(!$tmp_cart_id) {
                $tmp_cart_id = get_uniqid();
                set_session('ss_cart_id', $tmp_cart_id);
            }
        }

        // 보관된 회원장바구니 자료 cart id 변경
        if($member['mb_id'] && $tmp_cart_id) {
            $sql = " update {$g5['g5_shop_cart_table']}
                        set od_id = '$tmp_cart_id'
                        where mb_id = '{$member['mb_id']}'
                          and ct_direct = '0'
                          and ct_status = '쇼핑' ";
            sql_query($sql);
        }
    }
}

// 장바구니 건수 검사
function get_cart_count($cart_id)
{
    global $g5, $default;

    $sql = " select count(ct_id) as cnt from {$g5['g5_shop_cart_table']} where od_id = '$cart_id' ";
    $row = sql_fetch($sql);
    $cnt = (int)$row['cnt'];
    return $cnt;
}

//장바구니 간소 데이터 가져오기
function get_boxcart_datas($is_cache=false)
{
    global $g5;
    
    $cart_id = get_session("ss_cart_id");

    if( !$cart_id ){
        return array();
    }

    static $cache = array();

    if( $is_cache && !empty($cache) ){
        return $cache;
    }

    $sql  = " select * from {$g5['g5_shop_cart_table']} ";
    $sql .= " where od_id = '".$cart_id."' group by it_id ";
    $result = sql_query($sql);
    for ($i=0; $row=sql_fetch_array($result); $i++)
    {
        $key = $row['it_id'];
        $cache[$key] = $row;
    }

    return $cache;
}

//장바구니 간소 데이터 갯수 출력
function get_boxcart_datas_count()
{
    $cart_datas = get_boxcart_datas(true);

    return count($cart_datas);
}

// 장바구니 금액 체크 $is_price_update 가 true 이면 장바구니 가격 업데이트한다. 
function before_check_cart_price($s_cart_id, $is_ct_select_condition=false, $is_price_update=false, $is_item_cache=false){
    global $g5, $default, $config;

    if( !$s_cart_id ){
        return;
    }

    $select_where_add = '';

    if( $is_ct_select_condition ){
        $select_where_add = " and ct_select = '0' ";
    }

    $sql = " select * from `{$g5['g5_shop_cart_table']}` where od_id = '$s_cart_id' {$select_where_add} ";

    $result = sql_query($sql);
    $check_need_update = false;
    
    for ($i=0; $row=sql_fetch_array($result); $i++){
        if( ! $row['it_id'] ) continue;

        $it_id = $row['it_id'];
        $it = get_shop_item($it_id, $is_item_cache);
        
        $update_querys = array();

        if(!$it['it_id'])
            continue;
        
        if( $it['it_price'] !== $row['ct_price'] ){
            // 장바구니 테이블 상품 가격과 상품 테이블의 상품 가격이 다를경우
            $update_querys['ct_price'] = $it['it_price'];
        }

        if( $row['io_id'] ){
            $io_sql = " select * from {$g5['g5_shop_item_option_table']} where it_id = '{$it['it_id']}' and io_id = '{$row['io_id']}' ";
            $io_infos = sql_fetch( $io_sql );

            if( $io_infos['io_type'] ){
                $this_io_type = $io_infos['io_type'];
            }
            if( $io_infos['io_id'] && $io_infos['io_price'] !== $row['io_price'] ){
                // 장바구니 테이블 옵션 가격과 상품 옵션테이블의 옵션 가격이 다를경우
                $update_querys['io_price'] = $io_infos['io_price'];
            }
        }

        // 포인트
        $compare_point = 0;
        if($config['cf_use_point']) {

            // DB 에 io_type 이 1이면 상품추가옵션이며, 0이면 상품선택옵션이다
            if($row['io_type'] == 0) {
                $compare_point = get_item_point($it, $row['io_id']);
            } else {
                $compare_point = $it['it_supply_point'];
            }

            if($compare_point < 0)
                $compare_point = 0;
        }
        
        if((int) $row['ct_point'] !== (int) $compare_point){
            // 장바구니 테이블 적립 포인트와 상품 테이블의 적립 포인트가 다를경우
            $update_querys['ct_point'] = $compare_point;
        }

        if( $update_querys ){
            $check_need_update = true;
        }

        // 장바구니에 담긴 금액과 실제 상품 금액에 차이가 있고, $is_price_update 가 true 인 경우 장바구니 금액을 업데이트 합니다. 
        if( $is_price_update && $update_querys ){
            $conditions = array();

            foreach ($update_querys as $column => $value) {
                $conditions[] = "`{$column}` = '{$value}'";
            }

            if( $col_querys = implode(',', $conditions) ) {
                $sql_query = "update `{$g5['g5_shop_cart_table']}` set {$col_querys} where it_id = '{$it['it_id']}' and od_id = '$s_cart_id' and ct_id =  '{$row['ct_id']}' ";
                sql_query($sql_query, false);
            }
        }
    }

    // 장바구니에 담긴 금액과 실제 상품 금액에 차이가 있다면
    if( $check_need_update ){
        return false;
    }

    return true;
}

// 장바구니 상품삭제
function cart_item_clean()
{
    global $g5, $default;

    // 장바구니 보관일
    $keep_term = $default['de_cart_keep_term'];
    if(!$keep_term)
        $keep_term = 15; // 기본값 15일

    // ct_select_time이 기준시간 이상 경과된 경우 변경
    if(defined('G5_CART_STOCK_LIMIT'))
        $cart_stock_limit = G5_CART_STOCK_LIMIT;
    else
        $cart_stock_limit = 3;

    $stocktime = 0;
    if($cart_stock_limit > 0) {
        if($cart_stock_limit > $keep_term * 24)
            $cart_stock_limit = $keep_term * 24;

        $stocktime = G5_SERVER_TIME - (3600 * $cart_stock_limit);
        $sql = " update {$g5['g5_shop_cart_table']}
                    set ct_select = '0'
                    where ct_select = '1'
                      and ct_status = '쇼핑'
                      and UNIX_TIMESTAMP(ct_select_time) < '$stocktime' ";
        sql_query($sql);
    }

    // 설정 시간이상 경과된 상품 삭제
    $statustime = G5_SERVER_TIME - (86400 * $keep_term);

    $sql = " delete from {$g5['g5_shop_cart_table']}
                where ct_status = '쇼핑'
                  and UNIX_TIMESTAMP(ct_time) < '$statustime' ";
    sql_query($sql);
}
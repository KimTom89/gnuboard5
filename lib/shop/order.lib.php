<?php
// 주문의 금액, 배송비 과세금액 등의 정보를 가져옴
function get_order_info($od_id)
{
    global $g5;

    // 주문정보
    $sql = " select * from {$g5['g5_shop_order_table']} where od_id = '$od_id' ";
    $od = sql_fetch($sql);

    if(!$od['od_id'])
        return false;

    $info = array();

    // 장바구니 주문금액정보
    $sql = " select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as price,
                    SUM(cp_price) as coupon,
                    SUM( IF( ct_notax = 0, ( IF(io_type = 1, (io_price * ct_qty), ( (ct_price + io_price) * ct_qty) ) - cp_price ), 0 ) ) as tax_mny,
                    SUM( IF( ct_notax = 1, ( IF(io_type = 1, (io_price * ct_qty), ( (ct_price + io_price) * ct_qty) ) - cp_price ), 0 ) ) as free_mny
                from {$g5['g5_shop_cart_table']}
                where od_id = '$od_id'
                  and ct_status IN ( '주문', '입금', '준비', '배송', '완료' ) ";
    $sum = sql_fetch($sql);

    $cart_price = $sum['price'];
    $cart_coupon = $sum['coupon'];

    // 배송비
    $send_cost = get_sendcost($od_id);

    $od_coupon = $od_send_coupon = 0;

    if($od['mb_id']) {
        // 주문할인 쿠폰
        $sql = " select a.cp_id, a.cp_type, a.cp_price, a.cp_trunc, a.cp_minimum, a.cp_maximum
                    from {$g5['g5_shop_coupon_table']} a right join {$g5['g5_shop_coupon_log_table']} b on ( a.cp_id = b.cp_id )
                    where b.od_id = '$od_id'
                      and b.mb_id = '{$od['mb_id']}'
                      and a.cp_method = '2' ";
        $cp = sql_fetch($sql);

        $tot_od_price = $cart_price - $cart_coupon;

        if(isset($cp['cp_id']) && $cp['cp_id']) {
            $dc = 0;

            if($cp['cp_minimum'] <= $tot_od_price) {
                if($cp['cp_type']) {
                    $dc = floor(($tot_od_price * ($cp['cp_price'] / 100)) / $cp['cp_trunc']) * $cp['cp_trunc'];
                } else {
                    $dc = $cp['cp_price'];
                }

                if($cp['cp_maximum'] && $dc > $cp['cp_maximum'])
                    $dc = $cp['cp_maximum'];

                if($tot_od_price < $dc)
                    $dc = $tot_od_price;

                $tot_od_price -= $dc;
                $od_coupon = $dc;
            }
        }

        // 배송쿠폰 할인
        $sql = " select a.cp_id, a.cp_type, a.cp_price, a.cp_trunc, a.cp_minimum, a.cp_maximum
                    from {$g5['g5_shop_coupon_table']} a right join {$g5['g5_shop_coupon_log_table']} b on ( a.cp_id = b.cp_id )
                    where b.od_id = '$od_id'
                      and b.mb_id = '{$od['mb_id']}'
                      and a.cp_method = '3' ";
        $cp = sql_fetch($sql);

        if(isset($cp['cp_id']) && $cp['cp_id']) {
            $dc = 0;
            if($cp['cp_minimum'] <= $tot_od_price) {
                if($cp['cp_type']) {
                    $dc = floor(($send_cost * ($cp['cp_price'] / 100)) / $cp['cp_trunc']) * $cp['cp_trunc'];
                } else {
                    $dc = $cp['cp_price'];
                }

                if($cp['cp_maximum'] && $dc > $cp['cp_maximum'])
                    $dc = $cp['cp_maximum'];

                if($dc > $send_cost)
                    $dc = $send_cost;

                $od_send_coupon = $dc;
            }
        }
    }

    // 과세, 비과세 금액정보
    $tax_mny = $sum['tax_mny'];
    $free_mny = $sum['free_mny'];

    if($od['od_tax_flag']) {
        $tot_tax_mny = ( $tax_mny + $send_cost + $od['od_send_cost2'] )
                       - ( $od_coupon + $od_send_coupon + $od['od_receipt_point'] );
        if($tot_tax_mny < 0) {
            $free_mny += $tot_tax_mny;
            $tot_tax_mny = 0;
        }
    } else {
        $tot_tax_mny = ( $tax_mny + $free_mny + $send_cost + $od['od_send_cost2'] )
                       - ( $od_coupon + $od_send_coupon + $od['od_receipt_point'] );
        $free_mny = 0;
    }

    $od_tax_mny = round($tot_tax_mny / 1.1);
    $od_vat_mny = $tot_tax_mny - $od_tax_mny;
    $od_free_mny = $free_mny;

    // 장바구니 취소금액 정보
    $sql = " select SUM(IF(io_type = 1, (io_price * ct_qty), ((ct_price + io_price) * ct_qty))) as price
                from {$g5['g5_shop_cart_table']}
                where od_id = '$od_id'
                  and ct_status IN ( '취소', '반품', '품절' ) ";
    $sum = sql_fetch($sql);
    $cancel_price = $sum['price'];

    // 미수금액
    $od_misu = ( $cart_price + $send_cost + $od['od_send_cost2'] )
               - ( $cart_coupon + $od_coupon + $od_send_coupon )
               - ( $od['od_receipt_price'] + $od['od_receipt_point'] - $od['od_refund_price'] );

    // 장바구니상품금액
    $od_cart_price = $cart_price + $cancel_price;

    // 결과처리
    $info['od_cart_price']      = $od_cart_price;
    $info['od_send_cost']       = $send_cost;
    $info['od_coupon']          = $od_coupon;
    $info['od_send_coupon']     = $od_send_coupon;
    $info['od_cart_coupon']     = $cart_coupon;
    $info['od_tax_mny']         = $od_tax_mny;
    $info['od_vat_mny']         = $od_vat_mny;
    $info['od_free_mny']        = $od_free_mny;
    $info['od_cancel_price']    = $cancel_price;
    $info['od_misu']            = $od_misu;

    return $info;
}

// 주문서 번호를 얻는다.
function get_new_od_id()
{
    global $g5;

    // 주문서 테이블 Lock 걸고
    sql_query(" LOCK TABLES {$g5['g5_shop_order_table']} READ, {$g5['g5_shop_order_table']} WRITE ", FALSE);
    // 주문서 번호를 만든다.
    $date = date("ymd", time());    // 2002년 3월 7일 일경우 020307
    $sql = " select max(od_id) as max_od_id from {$g5['g5_shop_order_table']} where SUBSTRING(od_id, 1, 6) = '$date' ";
    $row = sql_fetch($sql);
    $od_id = $row['max_od_id'];
    if ($od_id == 0)
        $od_id = 1;
    else
    {
        $od_id = (int)substr($od_id, -4);
        $od_id++;
    }
    $od_id = $date . substr("0000" . $od_id, -4);
    // 주문서 테이블 Lock 풀고
    sql_query(" UNLOCK TABLES ", FALSE);

    return $od_id;
}

//주문데이터 또는 개인결제 주문데이터 가져오기
function get_shop_order_data($od_id, $type='item')
{
    global $g5;
    
    $od_id = preg_replace('/[^0-9a-z_-]/i', '', clean_xss_tags($od_id));

    if( $type == 'personal' ){
        $row = sql_fetch("select * from {$g5['g5_shop_personalpay_table']} where pp_id = $od_id ", false);
    } else {
        $row = sql_fetch("select * from {$g5['g5_shop_order_table']} where od_id = $od_id ", false);
    }

    return $row;
}

// 임시주문 데이터로 주문 필드 생성
function make_order_field($data, $exclude)
{
    $field = '';

    foreach($data as $key=>$value) {
        if(!empty($exclude) && in_array($key, $exclude))
            continue;

        if(is_array($value)) {
            foreach($value as $k=>$v) {
                $field .= '<input type="hidden" name="'.$key.'['.$k.']" value="'.$v.'">'.PHP_EOL;
            }
        } else {
            $field .= '<input type="hidden" name="'.$key.'" value="'.$value.'">'.PHP_EOL;
        }
    }

    return $field;
}

// 주문요청기록 로그를 남깁니다.
function add_order_post_log($msg='', $code='error'){
    global $g5, $member;
    
    if( empty($_POST) ) return;

    $post_data = base64_encode(serialize($_POST));
    $od_id = get_session('ss_order_id');

    if( $code === 'delete' ){
        sql_query(" delete from {$g5['g5_shop_post_log_table']} where (oid = '$od_id' and mb_id = '{$member['mb_id']}' and ol_code != 'error') OR ol_datetime < '".date('Y-m-d H:i:s', strtotime('-15 day', G5_SERVER_TIME))."' ", false);
        return;
    }

    if ( $code === 'error' ) {
        $result = sql_query("describe `{$g5['g5_shop_post_log_table']}`");
        while ($row = sql_fetch_array($result)){
            if( $row['Field'] === 'ol_msg' && $row['Type'] === 'varchar(255)' ){
                sql_query("ALTER TABLE `{$g5['g5_shop_post_log_table']}` MODIFY ol_msg TEXT NOT NULL;", false);
                sql_query("ALTER TABLE `{$g5['g5_shop_post_log_table']}` DROP PRIMARY KEY;", false);
                sql_query("ALTER TABLE `{$g5['g5_shop_post_log_table']}` ADD `log_id` int(11) NOT NULL AUTO_INCREMENT, ADD PRIMARY KEY (`log_id`);", false);
                break;
            }
        }
    }

    $sql = "insert into `{$g5['g5_shop_post_log_table']}`
            set oid = '$od_id',
            mb_id = '{$member['mb_id']}',
            post_data = '$post_data',
            ol_code = '$code',
            ol_msg = '".addslashes($msg)."',
            ol_datetime = '".G5_TIME_YMDHIS."',
            ol_ip = '{$_SERVER['REMOTE_ADDR']}'";

    if( $result = sql_query($sql, false) ){
        sql_query(" delete from {$g5['g5_shop_post_log_table']} where ol_datetime < '".date('Y-m-d H:i:s', strtotime('-15 day', G5_SERVER_TIME))."' ", false);
    } else {
        if(!sql_query(" DESC {$g5['g5_shop_post_log_table']} ", false)) {
            sql_query(" CREATE TABLE IF NOT EXISTS `{$g5['g5_shop_post_log_table']}` (
                          `log_id` int(11) NOT NULL AUTO_INCREMENT,
                          `oid` bigint(20) unsigned NOT NULL,
                          `mb_id` varchar(255) NOT NULL DEFAULT '',
                          `post_data` text NOT NULL,
                          `ol_code` varchar(255) NOT NULL DEFAULT '',
                          `ol_msg` text NOT NULL,
                          `ol_datetime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                          `ol_ip` varchar(25) NOT NULL DEFAULT '',
                          PRIMARY KEY (`log_id`)
                        ) ENGINE=MyISAM DEFAULT CHARSET=utf8; ", false);
        }
    }
}

// 구매 본인인증 체크
function shop_member_cert_check($id, $type)
{
    global $g5, $member;

    $msg = '';

    switch($type)
    {
        case 'item':
            $it = get_shop_item($id, true);

            $seq = '';
            for($i=0; $i<3; $i++) {
                $ca_id = $it['ca_id'.$seq];

                if(!$ca_id)
                    continue;

                $sql = " select ca_cert_use, ca_adult_use from {$g5['g5_shop_category_table']} where ca_id = '$ca_id' ";
                $row = sql_fetch($sql);

                if (($row['ca_cert_use'] || $row['ca_adult_use']) && strlen($member['mb_dupinfo']) == 64 && $member['mb_certify']) { // 본인 인증 된 계정 중에서 di로 저장 되었을 경우에만
                    goto_url(G5_BBS_URL."/member_cert_refresh.php?url=".urlencode(get_pretty_url($bo_table, $wr_id, $qstr)));
                }

                // 본인확인체크
                if($row['ca_cert_use'] && !$member['mb_certify']) {
                    if($member['mb_id'])
                        $msg = '회원정보 수정에서 본인확인 후 이용해 주십시오.';
                    else
                        $msg = '본인확인된 로그인 회원만 이용할 수 있습니다.';

                    break;
                }

                // 성인인증체크
                if($row['ca_adult_use'] && !$member['mb_adult']) {
                    if($member['mb_id'])
                        $msg = '본인확인으로 성인인증된 회원만 이용할 수 있습니다.\\n회원정보 수정에서 본인확인을 해주십시오.';
                    else
                        $msg = '본인확인으로 성인인증된 회원만 이용할 수 있습니다.';

                    break;
                }

                if($i == 0)
                    $seq = 1;
                $seq++;
            }

            break;
        case 'list':
            $sql = " select * from {$g5['g5_shop_category_table']} where ca_id = '$id' ";
            $ca = sql_fetch($sql);

            if (($ca['ca_cert_use'] || $ca['ca_adult_use']) && strlen($member['mb_dupinfo']) == 64 && $member['mb_certify']) { // 본인 인증 된 계정 중에서 di로 저장 되었을 경우에만
                goto_url(G5_BBS_URL."/member_cert_refresh.php?url=".urlencode(get_pretty_url($bo_table, $wr_id, $qstr)));
            
            }

            // 본인확인체크
            if($ca['ca_cert_use'] && !$member['mb_certify']) {
                if($member['mb_id'])
                    $msg = '회원정보 수정에서 본인확인 후 이용해 주십시오.';
                else
                    $msg = '본인확인된 로그인 회원만 이용할 수 있습니다.';
            }

            // 성인인증체크
            if($ca['ca_adult_use'] && !$member['mb_adult']) {
                if($member['mb_id'])
                    $msg = '본인확인으로 성인인증된 회원만 이용할 수 있습니다.\\n회원정보 수정에서 본인확인을 해주십시오.';
                else
                    $msg = '본인확인으로 성인인증된 회원만 이용할 수 있습니다.';
            }

            break;
        default:
            break;
    }

    return $msg;
}
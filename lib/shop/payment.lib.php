<?php
//이니시스의 삼성페이 또는 L.pay 결제 또는 카카오페이 가 활성화 되어 있는지 체크합니다.
function is_inicis_simple_pay(){
    global $default;

    if ( $default['de_samsung_pay_use'] || $default['de_inicis_lpay_use'] || $default['de_inicis_kakaopay_use'] ){
        return true;
    }

    return false;
}

//이니시스의 취소된 주문인지 또는 삼성페이 또는 L.pay 또는 이니시스 카카오페이 결제인지 확인합니다.
function is_inicis_order_pay($type){
    global $default, $g5;

    if( $default['de_pg_service'] === 'inicis' && get_session('P_TID') ){
        $tid = preg_replace('/[^A-Za-z0-9_\-]/', '', get_session('P_TID'));
        $sql = "select P_TID from `{$g5['g5_shop_inicis_log_table']}` where P_TID = '$tid' and P_STATUS = 'cancel' ";

        $row = sql_fetch($sql);

        if(isset($row['P_TID']) && $row['P_TID']){
            alert("이미 취소된 주문입니다.", G5_SHOP_URL);
        }
    }

    if( in_array($type, array('삼성페이', 'lpay', 'inicis_kakaopay') ) ){
        return true;
    }

    return false;
}



//결제방식 이름을 체크하여 치환 대상인 문자열은 따로 리턴합니다.
function check_pay_name_replace($payname, $od=array(), $is_client=0){

    if( $payname === 'lpay' ){
        return 'L.pay';
    } else if($payname === 'inicis_kakaopay'){
        return '카카오페이(KG이니시스)';
    } else if($payname === '신용카드'){
        if(isset($od['od_bank_account']) && $od['od_bank_account'] === '카카오머니'){
            return $payname.'(카카오페이)';
        }
    } else if($payname === '간편결제'){

        $add_str = $is_client ? '('.$payname.')' : '';

        if( isset($od['od_pg']) && $od['od_pg'] === 'lg' ){
            return 'PAYNOW';
        } else if( isset($od['od_pg']) && $od['od_pg'] === 'inicis' ){
            return 'KPAY';
        } else if( isset($od['od_pg']) && $od['od_pg'] === 'kcp' ){
            if( isset($od['od_other_pay_type']) && $od['od_other_pay_type'] === 'OT16' ){
                return '네이버페이_NHNKCP'.$add_str;
            } else if( isset($od['od_other_pay_type']) && ($od['od_other_pay_type'] === 'OT13' || $od['od_other_pay_type'] === 'NHNKCP_KAKAOMONEY') ){
                return '카카오페이_NHNKCP'.$add_str;
            }

            return 'PAYCO'.$add_str;
        }
    }

    return $payname;
}

function exists_inicis_shop_order($oid, $pp=array(), $od_time='', $od_ip='')
{

    $od_ip = $od_ip ? $od_ip : $_SERVER['REMOTE_ADDR'];

    //개인결제
    if( $pp ) {
        $hash_data = md5($pp['pp_id'].$pp['pp_price'].$pp['pp_time']);
        if( $hash_data == get_session('ss_personalpay_hash') ){
            // 개인결제번호제거
            set_session('ss_personalpay_id', '');
            set_session('ss_personalpay_hash', '');

            $uid = md5($pp['pp_id'].$pp['pp_time'].$od_ip);
            set_session('ss_personalpay_uid', $uid);
            
            goto_url(G5_SHOP_URL.'/personalpayresult.php?pp_id='.$pp['pp_id'].'&amp;uid='.$uid.'&amp;ini_noti=1');
        } else {
            goto_url(G5_SHOP_URL.'/personalpayresult.php?pp_id='.$pp['pp_id'].'&amp;ini_noti=1');
        }
    } else {    //그렇지 않으면
        if (!$od_time){
            $od_time = G5_TIME_YMDHIS;
        }

        if( $oid == get_session('ss_order_id') ){
            // orderview 에서 사용하기 위해 session에 넣고
            $uid = md5($oid.$od_time.$od_ip);
            set_session('ss_orderview_uid', $uid);
            goto_url(G5_SHOP_URL.'/orderinquiryview.php?od_id='.$oid.'&amp;uid='.$uid.'&amp;ini_noti=1');
        } else {
            goto_url(G5_SHOP_URL.'/orderinquiryview.php?od_id='.$oid.'&amp;ini_noti=1');
        }
    }
    return '';
}

function is_use_easypay($payname=''){
    global $default;

    $de_easy_pay_service_array = (isset($default['de_easy_pay_services']) && $default['de_easy_pay_services']) ? explode(',', $default['de_easy_pay_services']) : array();

    if($payname === 'global_nhnkcp' && $de_easy_pay_service_array && ('kcp' !== $default['de_pg_service'])){      // NHN_KCP 외 타PG 사용시
        if( in_array('global_nhnkcp_naverpay', $de_easy_pay_service_array) && ($default['de_card_test'] || (!$default['de_card_test'] && $default['de_kcp_mid'] && $default['de_kcp_site_key']) ) ){
            return true;
        }
    }

    return false;
}

function shop_is_taxsave($od, $is_view_receipt=false){
	global $default, $is_memeber;

	$od_pay_type = '';

	if( $od['od_settle_case'] == '무통장' ){
		$od_pay_type = 'account';
	} else if ( $od['od_settle_case'] == '계좌이체' ) {
		$od_pay_type = 'transfer';
	} else if ( $od['od_settle_case'] == '가상계좌' ) {
		$od_pay_type = 'vbank';
	}
	
	if( $od_pay_type ) {
		if( $default['de_taxsave_use'] && strstr( $default['de_taxsave_types'], $od_pay_type ) ){
			return 1;
		}
		
		// 아직 현금영수증 받기전 상태일때만
		if( $is_view_receipt && ! $od['od_cash'] && in_array($od['od_settle_case'], array('계좌이체', '가상계좌')) && ! strstr( $default['de_taxsave_types'], $od_pay_type ) ){
			return 2;
		}
	}

	return 0;
}
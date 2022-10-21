<?php
/**
 * 포인트 표시
 * @param float $point  포인트
 * @return string
 */
function display_point($point)
{
    return number_format($point) . G5_SHOP_POINT_UNIT;
}

/**
 * 상품별 지급 포인트 조회
 * @param array     $it     상품정보
 * @param string    $io_id  상품옵션ID
 * @return float    포인트
 */
function get_item_point($it, $io_id = '')
{
    global $g5;

    $it_point = 0;
    $it_price = $it['it_price'];
    
    // 상품 > 포인트 지급 유형
    switch ($it['it_point_type']) {
        case 0: // 설정금액
            $it_point = $it['it_point'];
            break;
        case 1: // 판매가 기준 설정비율
            $it_point = calculate_point($it_price, $it['it_point']);
            break;
        case 2: // 구매가 기준 설정비율
            if ($io_id) {
                $sql = "SELECT
                            io_id, io_price
                        FROM {$g5['g5_shop_item_option_table']}
                        WHERE it_id = '{$it['it_id']}'
                            AND io_id = '$io_id'
                            AND io_type = '0'
                            AND io_use = '1'";
                $opt = sql_fetch($sql);
                // 옵션가격 합산
                $it_price += ($opt['io_id'] ? $opt['io_price'] : 0);
            }
            $it_point = calculate_point($it_price, $it['it_point']);
            break;
    }
    
    return $it_point;
}

/**
 * 가격기준 설정비율로 포인트 계산
 * @param int $price    가격
 * @param int $ratio    비율
 * @param int $truncate 절삭단위
 * @return float|false  포인트
 */
function calculate_point($price, $ratio, $truncate = 10)
{
    return floor(($price * ($ratio / 100) / $truncate)) * $truncate;
}

/**
 * 주문포인트 적립
 * - 설정일이 지난 포인트 부여되지 않은 배송완료된 장바구니 자료에 포인트 부여
 * - 설정일이 0 이면 주문서 완료 설정 시점에서 포인트를 바로 부여합니다.
 * @param string $ct_status     포인트를 적립할 주문상태
 * @return void
 */
function save_order_point($ct_status = "완료")
{
    global $g5, $default;

    $beforedays = date("Y-m-d H:i:s", strtotime("-{$default['de_point_days']} day"));
    $sql = " SELECT
                *
            FROM {$g5['g5_shop_cart_table']}
            WHERE ct_status = '$ct_status'
                AND ct_point_use = '0'
                AND ct_time <= '$beforedays' ";
    $result = sql_query($sql);
    for ($i = 0; $row = sql_fetch_array($result); $i++) {
        $od_row = sql_fetch(" SELECT od_id, mb_id FROM {$g5['g5_shop_order_table']} WHERE od_id = '{$row['od_id']}' ");
        // 회원이면서 지급할 포인트가 0보다 크다면
        if ($od_row['mb_id'] && $row['ct_point'] > 0) {
            $po_point   = $row['ct_point'] * $row['ct_qty'];
            $po_content = "주문번호 {$od_row['od_id']} ({$row['ct_id']}) 배송완료";
            
            insert_point($od_row['mb_id'], $po_point, $po_content, "@delivery", $od_row['mb_id'], "{$od_row['od_id']}, {$row['ct_id']}");
        }
        // 포인트 지급확인 처리
        sql_query("UPDATE {$g5['g5_shop_cart_table']} SET ct_point_use = '1' WHERE ct_id = '{$row['ct_id']}' ");
    }
}

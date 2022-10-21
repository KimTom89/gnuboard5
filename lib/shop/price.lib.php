<?php
/**
 * 가격 표시
 * @param float $price 가격
 * @return string
 */
function display_price($price)
{
    return number_format($price) . G5_SHOP_MONETARY_UNIT;
}

/**
 * 판매가격 표시
 * @param array $item  상품정보
 * @return string
 */
function display_item_price($item)
{
    if ($item['it_tel_inq']) {
        return '전화문의';
    } else {
        return display_price((float)$item['it_price']);
    }
}

/**
 * 판매가격 출력
 * @param array $item  상품정보
 * @return int 판매가격
 */
function get_item_price($item)
{
    if ($item['it_tel_inq']) {
        return 0;
    } else {
        return (int)$item['it_price'];
    }
}

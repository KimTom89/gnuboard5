<?php
/**
 * 배송업체 목록을 selectbox option으로 출력
 * @param string $select_company    쇼핑몰설정 배송업체
 * @return string selectbox option
 */
function print_delivery_company_selectbox_option($select_company)
{
    $company_list = get_delivery_company_list();
    // 맨 앞에 자체배송 추가
    array_unshift($company_list, array("name" => "자체배송"));

    $option = '<option value="">없음</option> ' . PHP_EOL;
    foreach ($company_list as $company) {
        $option .= '<option value="' . $company['name'] . '" ' . get_selected($select_company, $company['name']) . '>' . $company['name'] . '</option>' . PHP_EOL;
    }

    return $option;
}

/**
 * 배송조회버튼 생성
 * @param string $od_company    배송회사
 * @param string $invoice       운송장번호
 * @param string $class         html button class
 */
function print_delivery_tracking_button($od_company, $invoice, $class = '')
{
    if (!$od_company || !$invoice) {
        return '';
    }
    $company_list = get_delivery_company_list();
    foreach ($company_list as $company) {
        if (strstr($company['name'], $od_company)) {
            $name   = $company['name'];
            $url    = $company['url'];
            $tel    = $company['tel'];
            break;
        }
    }

    $html = '';
    if (isset($name) && isset($url)) {
        $html_class  = ($class != '' ? ' class="' . $class . '"' : '');
        $html_tel    = (isset($tel) ? ' (문의전화: ' . $tel . ')' : '');

        $html .= '<a href="' . $url . $invoice . '" target="_blank" ' . $html_class . '>배송조회</a>' . $html_tel;
    }

    return $html;
}

/**
 * 배송업체 목록 조회
 * - 배송업체 문자열을 배열로 변환/조회 (./extend/shop.extend.php에서 define)
 * @return array 배송업체 목록
 */
function get_delivery_company_list()
{
    $delivery_company_list = array();

    $company_list = explode(")", str_replace("(", "", G5_DELIVERY_COMPANY));
    $count = count($company_list);
    for ($i = 0; $i < $count; $i++) {
        if (trim($company_list[$i]) == "") {
            continue;
        }
        list($name, $url, $tel) = explode("^", $company_list[$i]);
        array_push($delivery_company_list, array(
            "name" => $name,
            "url" => $url,
            "tel" => $tel
        ));
    }

    return $delivery_company_list;
}
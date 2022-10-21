<?php
// 상품 선택옵션
function get_item_options($it_id, $subject, $is_div='', $is_first_option_title='')
{
    global $g5;

    if(!$it_id || !$subject)
        return '';

    $sql = " select * from {$g5['g5_shop_item_option_table']} where io_type = '0' and it_id = '$it_id' and io_use = '1' order by io_no asc ";
    $result = sql_query($sql);
    if(!sql_num_rows($result))
        return '';

    $str = '';
    $subj = explode(',', $subject);
    $subj_count = count($subj);

    if($subj_count > 1) {
        $options = array();

        // 옵션항목 배열에 저장
        for($i=0; $row=sql_fetch_array($result); $i++) {
            $opt_id = explode(chr(30), $row['io_id']);

            for($k=0; $k<$subj_count; $k++) {
                if(! (isset($options[$k]) && is_array($options[$k])))
                    $options[$k] = array();

                if(isset($opt_id[$k]) && $opt_id[$k] && !in_array($opt_id[$k], $options[$k]))
                    $options[$k][] = $opt_id[$k];
            }
        }

        // 옵션선택목록 만들기
        for($i=0; $i<$subj_count; $i++) {
            $opt = $options[$i];
            $opt_count = count($opt);
            $disabled = '';
            if($opt_count) {
                $seq = $i + 1;
                if($i > 0)
                    $disabled = ' disabled="disabled"';

                if($is_div === 'div') {
                    $str .= '<div class="get_item_options">'.PHP_EOL;
                    $str .= '<label for="it_option_'.$seq.'" class="label-title">'.$subj[$i].'</label>'.PHP_EOL;
                } else {
                    $str .= '<tr>'.PHP_EOL;
                    $str .= '<th><label for="it_option_'.$seq.'" class="label-title">'.$subj[$i].'</label></th>'.PHP_EOL;
                }

                $select = '<select id="it_option_'.$seq.'" class="it_option"'.$disabled.'>'.PHP_EOL;

                $first_option_title = $is_first_option_title ? $subj[$i] : '선택';

                $select .= '<option value="">'.$first_option_title.'</option>'.PHP_EOL;
                for($k=0; $k<$opt_count; $k++) {
                    $opt_val = $opt[$k];
                    if(strlen($opt_val)) {
                        $select .= '<option value="'.$opt_val.'">'.$opt_val.'</option>'.PHP_EOL;
                    }
                }
                $select .= '</select>'.PHP_EOL;

                if($is_div === 'div') {
                    $str .= '<span>'.$select.'</span>'.PHP_EOL;
                    $str .= '</div>'.PHP_EOL;
                } else {
                    $str .= '<td>'.$select.'</td>'.PHP_EOL;
                    $str .= '</tr>'.PHP_EOL;
                }
            }
        }
    } else {
        if($is_div === 'div') {
            $str .= '<div class="get_item_options">'.PHP_EOL;
            $str .= '<label for="it_option_1">'.$subj[0].'</label>'.PHP_EOL;
        } else {
            $str .= '<tr>'.PHP_EOL;
            $str .= '<th><label for="it_option_1">'.$subj[0].'</label></th>'.PHP_EOL;
        }

        $select = '<select id="it_option_1" class="it_option">'.PHP_EOL;
        $select .= '<option value="">선택</option>'.PHP_EOL;
        for($i=0; $row=sql_fetch_array($result); $i++) {
            if($row['io_price'] >= 0)
                $price = '&nbsp;&nbsp;+ '.number_format($row['io_price']).'원';
            else
                $price = '&nbsp;&nbsp; '.number_format($row['io_price']).'원';

            if($row['io_stock_qty'] < 1)
                $soldout = '&nbsp;&nbsp;[품절]';
            else
                $soldout = '';

            $select .= '<option value="'.$row['io_id'].','.$row['io_price'].','.$row['io_stock_qty'].'">'.$row['io_id'].$price.$soldout.'</option>'.PHP_EOL;
        }
        $select .= '</select>'.PHP_EOL;
        
        if($is_div === 'div') {
            $str .= '<span>'.$select.'</span>'.PHP_EOL;
            $str .= '</div>'.PHP_EOL;
        } else {
            $str .= '<td>'.$select.'</td>'.PHP_EOL;
            $str .= '</tr>'.PHP_EOL;
        }

    }

    return $str;
}

// 상품 추가옵션
function get_item_supply($it_id, $subject, $is_div='', $is_first_option_title='')
{
    global $g5;

    if(!$it_id || !$subject)
        return '';

    $sql = " select * from {$g5['g5_shop_item_option_table']} where io_type = '1' and it_id = '$it_id' and io_use = '1' order by io_no asc ";
    $result = sql_query($sql);
    if(!sql_num_rows($result))
        return '';

    $str = '';

    $subj = explode(',', $subject);
    $subj_count = count($subj);
    $options = array();

    // 옵션항목 배열에 저장
    for($i=0; $row=sql_fetch_array($result); $i++) {
        $opt_id = explode(chr(30), $row['io_id']);

        if($opt_id[0] && !array_key_exists($opt_id[0], $options))
            $options[$opt_id[0]] = array();

        if(strlen($opt_id[1])) {
            if($row['io_price'] >= 0)
                $price = '&nbsp;&nbsp;+ '.number_format($row['io_price']).'원';
            else
                $price = '&nbsp;&nbsp; '.number_format($row['io_price']).'원';
            $io_stock_qty = get_option_stock_qty($it_id, $row['io_id'], $row['io_type']);

            if($io_stock_qty < 1)
                $soldout = '&nbsp;&nbsp;[품절]';
            else
                $soldout = '';

            $options[$opt_id[0]][] = '<option value="'.$opt_id[1].','.$row['io_price'].','.$io_stock_qty.'">'.$opt_id[1].$price.$soldout.'</option>';
        }
    }

    // 옵션항목 만들기
    for($i=0; $i<$subj_count; $i++) {
        $opt = (isset($subj[$i]) && isset($options[$subj[$i]])) ? $options[$subj[$i]] : array();
        $opt_count = count($opt);
        if($opt_count) {
            $seq = $i + 1;
            if($is_div === 'div') {
                $str .= '<div class="get_item_supply">'.PHP_EOL;
                $str .= '<label for="it_supply_'.$seq.'" class="label-title">'.$subj[$i].'</label>'.PHP_EOL;
            } else {
                $str .= '<tr>'.PHP_EOL;
                $str .= '<th><label for="it_supply_'.$seq.'">'.$subj[$i].'</label></th>'.PHP_EOL;
            }
            
            $first_option_title = $is_first_option_title ? $subj[$i] : '선택';

            $select = '<select id="it_supply_'.$seq.'" class="it_supply">'.PHP_EOL;
            $select .= '<option value="">'.$first_option_title.'</option>'.PHP_EOL;
            for($k=0; $k<$opt_count; $k++) {
                $opt_val = $opt[$k];
                if($opt_val) {
                    $select .= $opt_val.PHP_EOL;
                }
            }
            $select .= '</select>'.PHP_EOL;
            
            if($is_div === 'div') {
                $str .= '<span class="td_sit_sel">'.$select.'</span>'.PHP_EOL;
                $str .= '</div>'.PHP_EOL;
            } else {
                $str .= '<td class="td_sit_sel">'.$select.'</td>'.PHP_EOL;
                $str .= '</tr>'.PHP_EOL;
            }
        }
    }

    return $str;
}

function print_item_options($it_id, $cart_id)
{
    global $g5;

    $sql = " select ct_option, ct_qty, io_price
                from {$g5['g5_shop_cart_table']} where it_id = '$it_id' and od_id = '$cart_id' order by io_type asc, ct_id asc ";
    $result = sql_query($sql);

    $str = '';
    for($i=0; $row=sql_fetch_array($result); $i++) {
        if($i == 0)
            $str .= '<ul>'.PHP_EOL;
        $price_plus = '';
        if($row['io_price'] >= 0)
            $price_plus = '+';
        $str .= '<li>'.get_text($row['ct_option']).' '.$row['ct_qty'].'개 ('.$price_plus.display_price($row['io_price']).')</li>'.PHP_EOL;
    }

    if($i > 0)
        $str .= '</ul>';

    return $str;
}
<?php
  header('Access-Control-Allow-Origin: *');
  header('Access-Control-Allow-Methods: GET, POST');
  header("Access-Control-Allow-Headers: X-Requested-With");

  function get_substring($html, $container, $start, $end)
  {
    /** Нахождение места контейнера с товарами разных размеров */
    $content = stripos($html, $container);
    if ($content == -1) {
      return "string null";
    }
    /** Нахождения начала первого элемента в конетейнере range-revamp-seo-conten */
    $startIndex = stripos($html, $start, $offset = $content);
    if ($startIndex == -1) {
      return "string null";
    }
    /** Нахождения конца элементов в конетейнере range-revamp-seo-conten */
    $endIndex = stripos($html, $end, $offset = $startIndex);
    if ($endIndex <= 0) {
      return "string null";
    }
    /** Длина символов в контейнере range-revamp-seo-conten */
    $length = $endIndex - $startIndex;
    /** Строка с html, включающим все варианты товаров с разными размерами */
    $substr = substr($html, $startIndex, $length);
    if ($substr == -1) {
      return "string null";
    }
    return $substr;
  }

  function get_product_links($html, $container, $start, $end)
  {
    $links = [];
    $found = false;
    $current_html = $html;
    while ($found != true) {
      // Получение строки с ссылкой на неизвестный товар
      $product_link = get_substring($current_html, $container, $start, $end);

      if ($product_link == "string null") {
        $found = true;
      }
      else {
        array_push($links, $product_link);
        $removable_product = get_substring($current_html, "<a", "<", "</a>") . "</a>";
        if ($removable_product != "string null</a>") {
          $current_html = str_replace($removable_product, "", $current_html);
        }
      }
    }
    return $links;
  }

  if(!empty($_REQUEST['url'])) {
    // Принятие запроса с клиентской стороны
    $url = $_REQUEST['url'];
    // Получение контента страницы известного нам товара
    $html = file_get_contents($url);
    // Получение строки со всеми товарами в тегах <a>
    $product_links_html = get_substring($html, 'range-revamp-seo-content', '<', '</div>');

    if ($product_links_html == "string null") {
      header('X-PHP-Response-Code: 204', true, 204);
    }
    else {
      /**
       * Массив ссылок на страницы неизвестных нам товаров
       * Тип: array<string>
       */
      $product_links = get_product_links($product_links_html, '<a href="', 'http', '"');
      /**
       * Массив html-страниц неизвестных нам товаров
       * Тип: array<string>
       */
      echo '{"products":[';
      foreach ($product_links as $key=>$product_link) {
        $new_html = file_get_contents($product_link);
        echo "{";
        $id = get_substring($new_html, 'data-product=', '"', '" ');
        echo '"id": ' . $id . '",';
        $url = get_substring($new_html, '"og:url" content=', '"http', '/"');
        echo '"url": ' . $url . '",';
        $image = get_substring($new_html, '"og:image" content=', '"http', '"/>');
        echo '"image": ' . $image . '",';
        $edited_html = get_substring($new_html, 'range-revamp-product__buy-module-container', '<div', 'pip-container-first');
        // ---- Получение главных свойств товара
        $title_box = get_substring($edited_html, 'range-revamp-header-section__title', '>', '</');
        echo '"title":"' . substr_replace($title_box, '', 0, 1) . '",';
        $text_box = get_substring($edited_html, 'range-revamp-header-section__description-text', '>', '</');
        echo '"text":"' . substr_replace($text_box, '', 0, 1) . '",';
        $measurement_box = get_substring($edited_html, 'range-revamp-header-section__description-measurement', '>', '</');
        echo '"measurement":"' . substr_replace($measurement_box, '', 0, 1) . '",';
        $price = get_substring($edited_html, 'range-revamp-price__integer', '>', '</');
        echo '"price":"' . substr_replace($price, '', 0, 1) . '",';
        // ---- Получение дополнительных свойств товара (вытаскивать именно массивом)
        $edited_html_new = $edited_html;
        $removable_string = "";
        $index = 1;
        echo '"other_properties": [';
        while ($removable_string != "string null") {
          if ($index > 1) {
            echo ',';
          }
          echo "{";
          $category_box = get_substring($edited_html_new, 'range-revamp-chunky-header__title"', '>', '</');
          echo '"category":"' . substr_replace($category_box, '', 0, 1) . '",';
          $propertie_box = get_substring($edited_html_new, 'range-revamp-chunky-header__subtitle"', '>', '</');
          echo '"propertie":"' . substr_replace($propertie_box, '', 0, 1) . '"';
          $removable_string = get_substring($edited_html_new, "", "", 'range-revamp-chunky-header__subtitle"');
          $edited_html_new = str_replace($removable_string, "", $edited_html_new);
          $removable_string = get_substring($edited_html_new, "", "", 'range-revamp-chunky-header__title"');
          $edited_html_new = str_replace($removable_string, "", $edited_html_new);
          $index = $index + 1;
          echo "}";
        }
        echo "]";
        if ((count($product_links) - 1) != $key) {
          echo "},";
        }
        else {
          echo "}";
        }
      }
      echo ']}';
    }
  }
  else {
    header('X-PHP-Response-Code: 400', true, 400);
?>
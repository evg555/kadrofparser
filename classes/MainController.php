<?php

/**
* Основной контроллер парсера.
* Донор: http://vladivostok.farpost.ru/realty/sell_flats/
*
*/
set_time_limit(0);

class MainController
{
    public $siteUrl;
	public $part;
	public $lastDate;

	public function __construct($siteUrl,$part,$lastDate)
	{
		//Устанавливаем значения из конфига
        $this->siteUrl = $siteUrl;
        $this->part = $part;
        $this->lastDate = $lastDate;
	}

    public function worker(){
        //Получаем контент страницы с заказами
        $url = $this->siteUrl.$this->part;

        if ($content = file_get_contents($url)){
            $document = phpQuery::newDocument($content);

            //Находим даты постов не ранее последней
            $dates = explode("\n",$document->find(".date")->text());
            foreach ($dates as $date){
                $date = explode(" ",$date)[0];
                if ($date < $this->lastDate) break;
            }
        }
    }

	public function parseLinks($links){

		$document = new DOMDocument;
		$apartmentData = array();   //Данные о квартире

        foreach ($links as $link) {
            if (!strstr($link, 'bulletin/')) {
                if ($link == "help/PeriodicheskieUslugi") continue;
                if ($apartment = $this->http->getApartment($link)) {
                    if ($this->output) echo "Apartment URL: " . $link . "<br>";

                    $this->currentURL = $link;

                    //Загружаем контент в DOM
                    //@$document->loadHtml($apartment);
                    $dom = phpQuery::newDocument($apartment);

                    /*
					$contacts = "";

					//Проверяем на наличие каптчи
					if (strstr($contacts, 'name="captchaCrypt"'))
					{
						//Костыль - при создании новой сессии всегда вылетает каптча - так что, рекурсивно вызываем метод.
						if($this->test)
						{
							echo "BUGFIX: Sleep 15 sec. \r\n";
							$this->logWriter('BUGFIX, URL: '.$link.', page: #'.$this->pageNumber);
							sleep(15);
							$document = 0;
							$this->test = 0;
							$this->worker();
							return false;
						}

						$this->logWriter('captcha, URL: '.$link.', page: #'.$this->pageNumber);
						return false;
					}
					else
					{*/

                    //Получаем id объявления из URL
                    $getIdFromURL = explode('-', $link);
                    $apartmentData['ID'][] = str_replace('.html', '', end($getIdFromURL));

                    //preg_match("/span\stitle='.*?'>(.*?)<\/span/i", $apartment, $date);
                    //$apartmentData['Дата публикации'][] = mb_convert_encoding($date[1], 'utf-8', 'windows-1251');
                    //$apartmentData['Дата публикации'][] = $date[1];
                    $apartmentData['Дата публикации'][] = $dom->find("span.viewbull-header__actuality")->text();

                    //Получаем тип владельца
                    //$ownerType = $this->checkObject($this->getElementsByAttribute($document, 'isAgency', "data-field"));
                    $ownerType = $dom->find("span[data-field='isAgency']")->text();
                    //$ownerType = CreateDocument::fixEncoding($ownerType);
                    $apartmentData['Предложение от'][] = $ownerType;

                    //$apartmentData['Тип дома'][] = $this->checkObject($this->getElementsByAttribute($document, 'constructionStatus', "data-field"));
                    $apartmentData['Тип дома'][] = $this->parser->getTypeHouse($dom);
                    $apartmentData['Район'][] = $this->parser->getAddress($dom,"Район");
                    $apartmentData['Улица'][] = is_array($this->parser->getAddress($dom, "Адрес")) ? $this->parser->getAddress($dom, "Адрес")["street"] : "";
                    $apartmentData['Дом'][] = is_array($this->parser->getAddress($dom, "Адрес")) ? $this->parser->getAddress($dom, "Адрес")["house"] : "";

                    //$apartmentData['Тип квартиры'][] = $this->checkObject($this->getElementsByAttribute($document, 'flatType', "data-field"));
                    $apartmentData['Тип квартиры'][] = str_replace(["\n","\t"],"",$dom->find("span[data-field='flatType']")->text());
                    //$apartmentData['Площадь'][] = $this->checkObject($this->getElementsByAttribute($document, 'areaTotal', "data-field"));
                    $apartmentData['Площадь'][] = str_replace(["\n","\t"],"",$dom->find("span[data-field='areaTotal']")->text());

                    /*
                    if (empty($price = $this->checkObject($this->getElementsByAttribute($document, 'price', "itemprop")))){
                        $apartmentData['Цена'][] = $this->checkObject($this->getElementsByAttribute($document, 'price-agencySupportCommission', "data-field"));
                    }
                    else {
                        $apartmentData['Цена'][] = $price;
                    }
                    */
                    $apartmentData['Цена'][] = $dom->find("span[itemprop='price']")->text();
                    //$apartmentData['Заголовок'][] = $this->checkObject($this->getElementsByAttribute($document, 'subject', "data-field"));
                    $apartmentData['Заголовок'][] = $dom->find("span[data-field='subject']")->text();
                    /*
                    if (empty($text = $this->checkObject($this->getElementsByAttribute($document, 'text', "data-field")))){
                        $apartmentData['Описание'][] = $this->checkObject($this->getElementsByAttribute($document, 'realtyFeature', "data-field"));
                    }
                    else{
                        $apartmentData['Описание'][] = $text;
                    }
                    */
                    $apartmentData['Описание'][] = $this->parser->getDescription($dom);
                    //$apartmentData['Пользователь'][] = $this->checkObject($this->getElementsByAttribute($document, 'userNick', "class"));
                    $apartmentData['Пользователь'][] = $this->parser->getUsernick($dom);
                    //$apartmentData['Контакты'][] = empty($userContacts) ? '' : implode(', ', $userContacts);
                    $apartmentData['Ссылка'][] = "http://www.farpost.ru/".$link;

                }
            }
        }
		return $apartmentData;
	}



	/*
    public function getElementsByAttribute(DOMDocument $DOMDocument, $ClassName, $attribute = "class")
    {
        $Elements = $DOMDocument -> getElementsByTagName("*");
        $Matched = array();

        foreach($Elements as $node)
        {
            if( ! $node -> hasAttributes())
                continue;

            $classAttribute = $node -> attributes -> getNamedItem($attribute);

            if( ! $classAttribute)
                continue;

            $classes = explode(' ', $classAttribute -> nodeValue);

            if(in_array($ClassName, $classes))
                $Matched[] = $node;
        }

        return $Matched;
    }

    private function checkObject($obj)
    {
        return count($obj) ? trim($obj[0]->textContent) : '';
    }

    public function getContacts($contactsURL)
    {
        if($contacts = file_get_contents($contactsURL, false, $this->context))
        {
            return $contacts;
        }
        else
        {
            $this->logWriter('не удалось получить страницу с контактами, код ошибки:'.$http_response_header[0].' URL: '.$contactsURL.', page: '.$this->currentURL);
            return false;
        }
    }
	*/
}


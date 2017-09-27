<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class MainController
{
    public $siteUrl;
	public $parts;
	public $lastDate = LAST_DATE;
    public $addressTo;
    public $nameTo;
    private $_configFile = "";

	public function __construct($args)
	{
        //Устанавливаем значения из конфига
	    if (is_array($args)){
	        foreach ($args as $key=>$arg){
	            if (property_exists($this,$key)){
	                $this->$key = $arg;
                }
            }
        } else {
	        throw new \Exception("Ошибка в файле конфигурации. Должен быть массив данных!");
        }

        $this->_configFile = $_SERVER["DOCUMENT_ROOT"]."/config.php";
	}

    public function worker(){
	    foreach ($this->parts as $key=>$part){
            //Получаем контент страницы с заказами
            $url = $this->siteUrl.$part;

            if ($content = file_get_contents($url)){
                $document = phpQuery::newDocument($content);

                //Парсим данные
                $data = $this->parseData($document);

                $reestr = Reestr::getInstance();
                $reestr->setData($key,$data);
            }
        }

        $this->sendData($reestr->getData());

    }

	private function parseData($document){
        //Получаем список заявок
        $posts = $document->find(".project-list");
        $data = [];
        $i = 0;
        foreach($posts as $post){
            $pq = pq($post);

            //Находим даты постов не ранее последней
            $date = explode(" ",$pq->find(".date")->text())[0];
            if ($date < $this->lastDate) break;

            //Формируем данные
            $data[$i]['title'] = $pq->find(".project-title h6 a")->text();
            $data[$i]['link'] = "http://www.kadrof.ru".$pq->find(".project-title h6 a")->attr("href");
            $data[$i]['date'] = $pq->find(".date")->text();
            $data[$i]['price'] = $pq->find(".budget span")->text();

            $i++;
        }

        return $data;
	}

	private function sendData($data){
	    //Формируем тело письма
        $body = '';
        foreach ($data as $key=>$orders){
            $body .= "<h1>".$key."</h1><hr>";
            foreach ($orders as $order){
                $body .= "<h3><a href='".$order['link']."'>".$order['title']."</a></h3>";
                $body .= "<p> Дата размещения: ".$order['date']."</p>";
                $body .= "<p>Цена заявки: ".$order['price']."</p><br>";
            }
        }

	    //Отправляем e-mail
        $mail = new PHPMailer(true);
        try {
            /*
            //SMTP settings
            $mail->SMTPDebug = 2;
            $mail->isSMTP();
            $mail->Host = 'smtp1.example.com;smtp2.example.com';
            $mail->SMTPAuth = true;
            $mail->Password = 'secret';
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;
            */

            //Recipients
            $mail->setFrom('robot@evg-it.ru', 'Robot Paul');
            $mail->addAddress($this->addressTo, $this->nameTo);

            //Content
            $mail->isHTML(true);
            $mail->Subject = 'Новые заявки с сайта kadrof.ru';
            $mail->Body    = $body;

            $mail->send();
            echo 'Message has been sent';

            //Записывае новую дату в конфиг
            if (is_writable($this->_configFile)){
                $dataFile = file($this->_configFile);
                $dataFile[count($dataFile) - 1] = "define('LAST_DATE','".date("d.m.Y")."');";
                file_put_contents( $this->_configFile, $dataFile );
            }

        } catch (Exception $e) {
            file_put_contents("log.txt",date("d-m-Y H:i")." Message could not be sent.\r\nMailer Error: " . $mail->ErrorInfo);
        }
    }
}


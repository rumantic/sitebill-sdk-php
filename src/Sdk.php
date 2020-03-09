<?php
namespace Sitebill\SitebillSdkPhp;
class Sdk
{
    /**
     * @var $session_key
     */
    private $session_key;
    private $queryUrl;
    private $login;
    private $password;

    function __construct( $queryUrl, $login, $password ) {
        $this->queryUrl = $queryUrl;
        $this->login = $login;
        $this->password = $password;
    }

    /**
     * Подключение к API
     * @return bool
     */
    function connect () {
        $result = $this->get_auth($this->login, $this->password);
        if ($result['success']) {
            $this->session_key = $result['session_key'];
            return true;
        }
        return false;
    }

    /**
     * Загрузка списка колонок для модели
     * @param $model_name
     * @return array|mixed
     */
    function load_grid_columns ($model_name) {
        $params = array(
            'action' => 'model',
            'do' => 'load_grid_columns',
            'session_key' => $this->session_key,
            'model_name' => $model_name,
        );
        $result = $this->executeHTTPRequest($this->queryUrl, $params);
        return $result;
    }

    /**
     * Добавление записи к модели
     * @param $model_name
     * @param $ql_items
     * @return array|mixed
     */
    function native_insert ( $model_name, $ql_items ) {
        $params = array(
            'action' => 'model',
            'do' => 'native_insert',
            'session_key' => $this->session_key,
            'model_name' => $model_name,
            'ql_items' => $ql_items,
        );
        $result = $this->executeHTTPRequest($this->queryUrl, $params);
        return $result;
    }

    /**
     * Удаляем запись из таблицы
     * @param $model_name
     * @param $primary_key
     * @param $key_value
     * @return array|mixed
     */
    function delete ( $model_name, $primary_key, $key_value ) {
        $params = array(
            'action' => 'model',
            'do' => 'delete',
            'session_key' => $this->session_key,
            'model_name' => $model_name,
            'primary_key' => $primary_key,
            'key_value' => $key_value,
        );
        $result = $this->executeHTTPRequest($this->queryUrl, $params);
        return $result;
    }

    /**
     * Загружаем данные о записи из таблицы
     * @param $model_name
     * @param $primary_key
     * @param $key_value
     * @return array|mixed
     */
    function load_data ( $model_name, $primary_key, $key_value ) {
        $params = array(
            'action' => 'model',
            'do' => 'load_data',
            'session_key' => $this->session_key,
            'model_name' => $model_name,
            'primary_key' => $primary_key,
            'key_value' => $key_value,
        );
        $result = $this->executeHTTPRequest($this->queryUrl, $params);
        return $result;
    }

    function upload_image ($model_name, $primary_key, $key_value, $image_field, $filename) {
        /*
        this.url = this.api_url + '/apps/api/rest.php?uploader_type=dropzone&element='
            + this.image_field
            + '&model=' + this.entity.get_table_name()
            + '&layer=native_ajax'
            + '&is_uploadify=1'
            + '&primary_key_value=' + this.entity.primary_key
            + '&primary_key=' + this.entity.key_value
            + '&session_key=' + this.modelSerivce.get_session_key();
        $curlfile = curl_file_create(__DIR__.DIRECTORY_SEPARATOR.'1.jpg');
        */

        $get = array(
            'uploader_type' => 'dropzone',
            'element' => $image_field,
            'model' => $model_name,
            'layer' => 'native_ajax',
            'is_uploadify' => '1',
            'primary_key' => $primary_key,
            'primary_key_value' => $key_value,
            'session_key' => $this->session_key,
        );
        $get_string = implode('&', array_map(
            function ($v, $k) {
                if(is_array($v)){
                    return $k.'='.implode('&'.$k.'=', $v);
                }else{
                    return $k.'='.$v;
                }
            },
            $get,
            array_keys($get)
        ));

        $params = array(
            'file' => '@'.realpath($filename),
            //'file' => __DIR__.'/'.$filename,
        );
        //echo '@'.__DIR__.'/'.$filename.'<br>';
        echo '<pre>';
        print_r($params);
        echo '</pre>';

        // initialise the curl request
        $request = curl_init('http://sdk/test.php');

        // send a file
        curl_setopt($request, CURLOPT_POST, true);
        curl_setopt(
            $request,
            CURLOPT_POSTFIELDS,
            array(
                'file' => '@' . realpath($filename)
            ));
        // output the response
        curl_setopt($request, CURLOPT_RETURNTRANSFER, true);
        echo curl_exec($request);

        // close the session
        curl_close($request);
        return 'test';

        //echo '@'.__DIR__.'/'.$filename.'<br>';
        echo $this->queryUrl.'?'.$get_string.'<br>';
        $result = $this->executeHTTPRequest($this->queryUrl.'?'.$get_string, $params, true);
        return $result;
    }


    /**
     * Получаем ключ session_key по логину и паролю
     * @param $login
     * @param $password
     * @return array|mixed
     */
    function get_auth ( $login, $password ) {
        $params = array(
            'action' => 'oauth',
            'do' => 'login',
            'login' => $login,
            'password' => $password,
        );
        $result = $this->executeHTTPRequest($this->queryUrl, $params);
        return $result;
    }

    private function executeHTTPRequest ($queryUrl, array $params = array(), $disable_http_build = false) {
        $result = array();
        if ( !$disable_http_build ) {
            $queryData = http_build_query($params);
        } else {
            $queryData = $params;
        }

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_POST => 1,
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $queryUrl,
            CURLOPT_POSTFIELDS => $queryData,
        ));

        $curlResult = curl_exec($curl);

        echo '<pre>';
        print_r($curlResult);
        echo '</pre>';
        curl_close($curl);

        if ($curlResult != '') {
            $result = json_decode($curlResult, true);
        } else {
            $result = array('error' => 'query failed');
        }

        return $result;
    }
}


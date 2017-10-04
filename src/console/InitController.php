<?php

namespace yii\pinxter\deploy\helper;


use Yii;
use yii\console\Controller;
use yii\httpclient\Client;

/**
 * Created by PhpStorm.
 * User: Jelezka
 * Date: 03.10.2017
 * Time: 16:46
 */
class InitController extends Controller
{

    /**
     * Types of Environments
     */
    const ENV_INVALID = 0;
    const ENV_PROD = 1;
    const ENV_DEV = 2;
    const ENV_TEST = 3;

    /**
     * Names of Environments
     * @var array
     */
    protected $environments = [
        self::ENV_DEV => 'dev',
        self::ENV_PROD => 'prod',
        self::ENV_TEST => 'test',
    ];

    /**
     * Current name of Environment
     * @var
     */
    protected $currentEnvironmentName;

    /**
     * Current token
     * @var
     */
    protected $token;

    /**
     * Current Environment ID
     * @var int
     */
    protected $environmentId = 0;

    /**
     * Config directory where files will be save
     * @var
     */
    protected $configDirectory;


    /**
     * Generates config files
     * `````````````````
     * Type in console:
     *     yii init/config prod - for production server
     *     yii init/config dev - for developer server
     *     yii init/config test - for test server
     * @param string $env (prod | dev | test) type of server
     */
    public function actionConfig($env)
    {
        $this->currentEnvironmentName = (int) array_search($env, $this->environments);
        $this->configDirectory = Yii::getAlias('@common') . DIRECTORY_SEPARATOR . 'config/';
        if (file_exists($this->configDirectory . 'core.php')) {
            $config = require_once $this->configDirectory . 'core.php';
            if ($this->login($config)) {
                $this->generateConfig($this->configDirectory . 'token',
                    [
                        'token' => $this->token,
                        'env' => $this->environmentId
                    ]);
                $this->getConfigParams($config);
            }

        }
    }

    /**
     * Get configurations from API
     * @param array $config
     * @return bool
     */
    protected function getConfigParams($config)
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('get')
            ->setHeaders(['env' => $this->environmentId, 'access-token' => $this->token])
            ->setUrl($config['super_url'] . 'apps-props')
            ->send();

        if ($response->isOk) {
            $result = json_decode($response->content, true);
            $this->makeConfigurationFiles($result);
        }

        return $response->isOk;
    }

    /**
     * Sort and put to file all received config
     * @param $data
     * @return array
     */
    protected function makeConfigurationFiles($data)
    {
        $result = [];
        foreach ($data as $item) {
            $result[$item['prop_group']][] = $item;
        }

        foreach ($result as $group => $item) {
            $config = [];
            foreach ($item as $param) {
                $config[$param['prop_name']] = $param['prop_value'];
            }
            $this->generateConfig($this->configDirectory . $group, $config);
        }

        return $result;

    }

    /**
     * Retrieve token and environment ID
     * @param $config
     * @return mixed
     */
    protected function login($config)
    {
        $client = new Client();
        $response = $client->createRequest()
            ->setMethod('post')
            ->setHeaders(['env' => $this->currentEnvironmentName])
            ->setUrl($config['super_url'] . 'apps/login')
            ->setData(['app_key' => $config['key']])
            ->send();

        if ($response->isOk) {
            $result = json_decode($response->content);
            $this->token = $result->token;
            $this->environmentId = $result->env;
        }

        return $response->isOk;
    }

    /**
     * Put content to PHP configuration file
     * @param string $fileName
     * @param array $data
     * @return bool|int
     */
    protected function generateConfig($fileName, $data)
    {
        $openTag = "<?php\r\n return ";
        $content = str_replace(')', ']',str_replace('array (', '[', var_export($data, true)));
        $content = $openTag . $content . ';';

        return file_put_contents($fileName . '.php', $content);
    }
}
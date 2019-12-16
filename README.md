## firelens-fluentbit
aws firelensを使ったfluentbit/datadog/s3連携のユースケース

https://docs.aws.amazon.com/en_us/AmazonECS/latest/userguide/using_firelens.html#firelens-using-fluentbit

### Overview
docker-composeで、３つのコンテナが生成されます。
- web:アクセスログ/エラーログを、標準出力/標準エラー出力するアプリケーションコンテナ
- fluentbit:webからの標準出力を取得、タグで分類、datadog/s3に送信するログ管理コンテナ
- firelens-fluentbit:firelensで使用する,カスタムのdocker imageを生成するコンテナ。生成後速やかに停止します。生成されたイメージをECR等のdocker registryに配置し、firelensで呼び出します。

### 確認手順
#### ローカル
- .envファイルに、datadog/s3の認証情報を記載する
- コンテナ起動/生成
`docker-compose up -d --build`
- webコンテナを開く
`http://localhost`
- webコンテナを開き、人為的エラーを出力
`http://localhost?exception`
- webコンテナを開き、人為的アプリケーションログを出力
`http://localhost?application`
- datadogから、エラーログ、アプリケーションログが管理されていることを確認する
- S3から、アクセスログ、エラーログ、アプリケーションログがアップロードされていることを確認する
- 生成されたwebと、firelens-fluentbitのdocker-imageを、docker registryにpush
#### firelens
- webコンテナで、ECS（fargate）を起動する
- カスタムロギングのURLを参照し、カスタムfluentbit/カスタム構成ファイルの設定を行う
https://docs.aws.amazon.com/en_us/AmazonECS/latest/userguide/using_firelens.html#firelens-using-fluentbit

- webコンテナのロギングドライバをJSONに設定する
```
    "logConfiguration": {
        "logDriver": "awsfirelens",
        "secretOptions": null,
        "options": null
    },
```
- containerDefinitionsに、log-routerコンテナの設定を追加する
- fluentbitコンテナのログの出力先は、awslogにする（clowdwatchでロググループを作成しておく）
- environment追加
- imageに、先ほど生成したfirelens-fluentbitのdocker-imageを指定する
- firelensConfigurationで、上記docker-imageのカスタム構成ファイルを指定する
```
        {
            "logConfiguration": {
                "logDriver": "awslogs",
                "secretOptions": null,
                "options": {
                    "awslogs-group": "/ecs/first-run-task-definition",
                    "awslogs-region": "ap-northeast-1",
                    "awslogs-stream-prefix": "firelens"
                }
            },
            "environment": [
                {
                    "name": "OUT_DATADOG_API_KEY",
                    "value": "*********"
                },
                {
                    "name": "OUT_DATADOG_SOURCE",
                    "value": "web-container"
                },
                {
                    "name": "OUT_DATADOG_SERVICE_ERROR",
                    "value": "web-container"
                },
                {
                    "name": "OUT_DATADOG_SERVICE_APPLICATION",
                    "value": "*********"
                }       
                {
                    "name": "OUT_S3_ACCESS_KEY",
                    "value": "*********"
                },
                {
                    "name": "OUT_S3_SECRET_ACCESS_KEY",
                    "value": "*********"
                },                
                {
                    "name": "OUT_S3_BUCKET",
                    "value": "*********"
                },
                {
                    "name": "OUT_S3_PREFIX_ACCESS",
                    "value": "*********"
                },
                {
                    "name": "OUT_S3_PREFIX_ERROR",
                    "value": "*********"
                },
                {
                    "name": "OUT_S3_PREFIX_APPLICATION",
                    "value": "*********"
                }
            ],
            "image": "*******/firelens:firelens-fluentbit",
            "firelensConfiguration": {
                "type": "fluentbit",
                "options": {
                    "config-file-type": "file",
                    "enable-ecs-log-metadata": "true",
                    "config-file-value": "/fluent-bit/etc/fluent_bit_custom.conf"
                }
            },
            "name": "log-router"
        }
```
- fargateで起動したコンテナにアクセスし、datadog/s3にログの反映がされることを確認する

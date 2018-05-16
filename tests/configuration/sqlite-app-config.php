<?php
/**\n * Configuration File\n * \n * Add your local configuration according to the documentation of the the application here\n * 
 */
     
return [
    'id' => 'config-basic',
    'components' => [
        'db' => [
            'class' => 'yii\db\Connection',
            'dsn' => 'sqlite:@runtime/sqlite-db.sql',
        ]
    ]
];     

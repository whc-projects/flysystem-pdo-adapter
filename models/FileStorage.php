<?php

namespace hossein142001\Flysystem\models;

use Yii;
use yii\db\ActiveRecord;


/**
 * This is the model class for table "{{%file_storage}}".
 *
 * @property integer $id
 * @property string $path
 * @property string $type
 * @property resource $contents
 * @property integer $size
 * @property string $mimetype
 * @property integer $timestamp
 *
 * @property User $createdUser
 * @property User $modifiedUser
 */
class BaseFileStorage extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%file_storage}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['path', 'type'], 'required' , 'except' => 'getByParams'],
            [['contents'], 'string'],
            [['size', 'timestamp'], 'integer'],
            [['created_time', 'modified_time', 'deleted_time'], 'safe'],
            [['path'], 'string', 'max' => 255],
            [['type'], 'string', 'max' => 15],
            [['mimetype'], 'string', 'max' => 127],
            [['path'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t( 'ID'),
            'path' => Yii::t('Path'),
            'type' => Yii::t('Type'),
            'contents' => Yii::t('Contents'),
            'size' => Yii::t('Size'),
            'mimetype' => Yii::t('Mimetype'),
            'timestamp' => Yii::t('Timestamp')
        ];
    }
}

<?php

namespace whc\Flysystem\Adapter;

use whc\Flysystem\Adapter\models\FileStorage;

use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\AdapterInterface;
use League\Flysystem\Config;
use League\Flysystem\Util;
use \PDO;

/**
 * Class PDOAdapter
 */
class PDOAdapter implements AdapterInterface
{
    use NotSupportingVisibilityTrait;

    /**
     * @var \PDO
     */
    protected $pdo;

    /**
     * @var string
     */
    protected $table;

    /**
     * PDOAdapter constructor.
     *
     * @param PDO $pdo
     */
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;

        $this->table = '{{%file_storage}}';
    }

    /**
     * {@inheritdoc}
     */
    public function write($path, $contents, Config $config = null)
    {
        $size = strlen($contents);
        $type = 'file';
        $mimetype = Util::guessMimeType($path, $contents);
        $timestamp = time();

        $model = new FileStorage;
        $model->path = $path;
        $model->contents = $contents;
        $model->size = $size;
        $model->type = $type;
        $model->mimetype = $mimetype;
        $model->timestamp = $timestamp;
        $model->save();

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function writeStream($path, $resource, Config $config = null)
    {
        return $this->write($path, stream_get_contents($resource), $config);
    }

    /**
     * {@inheritdoc}
     */
    public function update($path, $contents, Config $config = null)
    {

        $size = strlen($contents);
        $mimetype = Util::guessMimeType($path, $contents);

        $model = FileStorage::find()->andWhere(['path' => $path])->one();

        $model->contents = $contents;
        $model->size = $size;
        $model->mimetype = $mimetype;
        $model->save();

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function updateStream($path, $resource, Config $config = null)
    {
        return $this->update($path, stream_get_contents($resource), $config);
    }

    /**
     * {@inheritdoc}
     */
    public function rename($path, $newpath)
    {

        $model = FileStorage::find()->andWhere(['path' => $path])->one();
        if ($model->type === 'dir') {
            $dirContents = $this->listContents($path, true);

            $pathLength = strlen($path);

            foreach ($dirContents as $object) {
                $currentObjectPath = $object['path'];
                $newObjectPath = $newpath . substr($currentObjectPath, $pathLength);

                $subModel = FileStorage::find()->andWhere(['path' => $currentObjectPath])->one();
                $subModel->path = $newObjectPath;
                $subModel->save();
            }
        }

        $model->path = $newpath;
        $model->save();

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $model = FileStorage::find()->andWhere(['path' => $path])->one();


        if (!empty($model)) {
            $newModel = new FileStorage();
            $newModel->path = $newpath;
            $newModel->contents = $model->contents;
            $newModel->size = $model->size;
            $newModel->type = $model->type;
            $newModel->mimetype = $model->mimetype;
            $newModel->timestamp = $model->timestamp;
            $newModel->save();

            return $newModel;
        }
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        return FileStorage::find()->andWhere(['path' => $path])->one()->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function deleteDir($dirname)
    {
        $dirContents = $this->listContents($dirname, true);

        if (!empty($dirContents)) {
            foreach ($dirContents as $object) {
                FileStorage::find()->andWhere(['path' => $object->path])->one()->delete();
            }
        }
        return FileStorage::find()->andWhere(['path' => $object->path, 'type' => 'dir'])->one()->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function createDir($dirname, Config $config = null)
    {
        $newModel = new FileStorage();

        $newModel->path = $dirname;
        $newModel->type = 'dir';
        $newModel->timestamp = time();
        $newModel->save();

        return $newModel;
    }

    /**
     * {@inheritdoc}
     */
    public function has($path)
    {
        return FileStorage::find()->andWhere(['path' => $path])->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        $model = FileStorage::find()->andWhere(['path' => $path])->one();
        if ($model)
            return $model->contents;
        else
            return false;
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        $stream = fopen('php://temp', 'w+');
        $result = $this->read($path);

        if (!$result) {
            fclose($stream);

            return false;
        }

        fwrite($stream, $result['contents']);
        rewind($stream);

        return compact('stream');
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        $model = FileStorage::find();
        $useWhere = (bool)strlen($directory);
        if ($useWhere) {
            $pathPrefix = $directory . '/%';
            $model->andWhere([
                'OR',
                ['LIKE', 'path', $directory],
                ['LIKE', 'path', $pathPrefix],
            ]);
        }
        $result = $model->asArray()->all();

        $result = array_map(function ($v) {
            $v['timestamp'] = (int)$v['timestamp'];
            $v['size'] = (int)$v['size'];
            $v['dirname'] = Util::dirname($v['path']);

            if ($v['type'] === 'dir') {
                unset($v['mimetype']);
                unset($v['size']);
                unset($v['contents']);
            }

            return $v;
        }, $result);

        return $recursive ? $result : Util::emulateDirectories($result);
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $model = FileStorage::find()->andWhere(['path' => $path])->asArray()->one();
        if ($model)
            return $model;
        else
            return false;
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        return $this->getMetadata($path);
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        return $this->getMetadata($path);
    }
}

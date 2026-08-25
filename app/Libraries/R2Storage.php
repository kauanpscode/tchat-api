<?php

namespace App\Libraries;

use Aws\S3\S3Client;

class R2Storage
{
    private S3Client $client;
    private string $bucket;
    private string $publicUrl;

    public function __construct()
    {
        $this->bucket = env('r2.bucket');
        $this->publicUrl = env('r2.public_url');

        $options = array(
            'region' => 'auto', // Required by SDK but not used by R2
            'endpoint' => env('r2.endpoint'),
            'version' => 'latest',
            'credentials' => array(
                'key' => env('r2.access_key'),
                'secret' => env('r2.secret_key')
            )
        );

        $this->client = new S3Client($options);
    }

    public function upload($file, string $folder = 'uploads'): array
    {
        $key = $folder . '/' . uniqid() . '_' . $file->getClientName();

        $this->client->putObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
            'Body' => fopen($file->getTempName(), 'rb'),
            'ContentType' => $file->getMimeType(),
        ]);

        return [
            'key' => $key,
            'url' => rtrim($this->publicUrl, '/') . '/' . $key,
            'name' => $file->getClientName(),
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ];
    }

    public function delete(string $key): bool
    {
        $this->client->deleteObject([
            'Bucket' => $this->bucket,
            'Key' => $key,
        ]);

        return true;
    }
}

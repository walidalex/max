<?php
declare(strict_types=1);
namespace App\Core\Http;

final class UploadedFile
{
    public function __construct(
        private readonly string $temporaryPath,
        private readonly string $originalName,
        private readonly int $error,
        private readonly int $size,
    ) {}

    /** @param array{name?:mixed,tmp_name?:mixed,error?:mixed,size?:mixed} $file */
    public static function fromArray(array $file): self
    {
        return new self((string)($file['tmp_name']??''),(string)($file['name']??''),(int)($file['error']??UPLOAD_ERR_NO_FILE),(int)($file['size']??0));
    }
    public function isPresent(): bool { return $this->error !== UPLOAD_ERR_NO_FILE; }
    public function isValid(): bool { return $this->error === UPLOAD_ERR_OK && $this->temporaryPath !== '' && is_file($this->temporaryPath); }
    public function error(): int { return $this->error; }
    public function size(): int { return $this->size; }
    public function originalName(): string { return $this->originalName; }
    public function path(): string { return $this->temporaryPath; }
    public function mimeType(): ?string
    {
        if (!$this->isValid()) { return null; }
        $mime=(new \finfo(FILEINFO_MIME_TYPE))->file($this->temporaryPath);
        return is_string($mime)?$mime:null;
    }
    /** @return array{0:int,1:int}|null */
    public function dimensions(): ?array
    {
        if (!$this->isValid()) { return null; }
        $size=@getimagesize($this->temporaryPath);
        return is_array($size)?[(int)$size[0],(int)$size[1]]:null;
    }
    public function moveTo(string $destination): void
    {
        $moved=is_uploaded_file($this->temporaryPath)?move_uploaded_file($this->temporaryPath,$destination):(PHP_SAPI==='cli'&&rename($this->temporaryPath,$destination));
        if(!$moved){throw new \RuntimeException('Unable to move uploaded file.');}
    }
}

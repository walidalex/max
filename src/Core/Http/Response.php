<?php

declare(strict_types=1);

namespace App\Core\Http;

final class Response
{
    /** @param array<string, string> $headers */
    public function __construct(private readonly string $content = '', private readonly int $status = 200, private readonly array $headers = [], private readonly ?string $filePath = null) {}

    public static function html(string $content, int $status = 200): self { return new self($content, $status, ['Content-Type' => 'text/html; charset=UTF-8']); }
    public static function json(array $data, int $status = 200): self { return new self((string) json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $status, ['Content-Type' => 'application/json; charset=UTF-8']); }
    public static function redirect(string $url, int $status = 302): self { return new self('', $status, ['Location' => $url]); }
    public static function file(string $path, string $contentType): self
    {
        return new self('',200,['Content-Type'=>$contentType,'Content-Length'=>(string)filesize($path),'X-Content-Type-Options'=>'nosn'],$path);
    }
    public function status(): int { return $this->status; }
    public function content(): string { return $this->content; }
    public function send(): never
    {
        http_response_code($this->status);
        foreach ($this->headers as $name => $value) { header($name . ': ' . $value); }
        if($this->filePath!==null){$stream=fopen($this->filePath,'rb');if($stream===false){exit;}fpassthru($stream);fclose($stream);}else{echo $this->content;}
        exit;
    }
}

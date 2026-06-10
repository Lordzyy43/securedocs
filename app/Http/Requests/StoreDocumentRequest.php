<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rules\File;
use ZipArchive;

class StoreDocumentRequest extends FormRequest
{
  public function authorize(): bool
  {
    return true;
  }

  /**
   * @return array<string, array<int, string>>
   */
  public function rules(): array
  {
    return [
      'document' => [
        'required',
        'file',
        File::types(['pdf', 'docx', 'xlsx', 'jpg', 'jpeg', 'png'])->max(10 * 1024),
      ],
    ];
  }

  public function withValidator($validator): void
  {
    $validator->after(function ($validator) {
      if (! $this->hasFile('document')) {
        return;
      }

      $file = $this->file('document');

      if (! $file instanceof UploadedFile || ! $this->isValidDocumentMime($file)) {
        $validator->errors()->add('document', 'The uploaded file MIME type is not allowed or does not match the file contents.');
      }
    });
  }

  protected function isValidDocumentMime(UploadedFile $file): bool
  {
    $allowedMimeTypes = [
      'pdf' => ['application/pdf'],
      'docx' => [
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/zip',
        'application/octet-stream',
      ],
      'xlsx' => [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/octet-stream',
      ],
      'jpg' => ['image/jpeg'],
      'jpeg' => ['image/jpeg'],
      'png' => ['image/png'],
    ];

    $extension = strtolower($file->getClientOriginalExtension());
    $mime = $file->getMimeType();

    if (! isset($allowedMimeTypes[$extension])) {
      return false;
    }

    if (! in_array($mime, $allowedMimeTypes[$extension], true)) {
      return false;
    }

    return $this->checkMagicBytes($file, $extension);
  }

  protected function checkMagicBytes(UploadedFile $file, string $extension): bool
  {
    $path = $file->getRealPath();

    if (! $path || ! is_readable($path)) {
      return false;
    }

    $handle = fopen($path, 'rb');

    if (! $handle) {
      return false;
    }

    $signature = fread($handle, 8);
    fclose($handle);

    return match ($extension) {
      'pdf' => str_starts_with($signature, '%PDF-'),
      'jpg', 'jpeg' => str_starts_with($signature, "\xFF\xD8\xFF"),
      'png' => str_starts_with($signature, "\x89PNG\x0D\x0A\x1A\x0A"),
      'docx', 'xlsx' => str_starts_with($signature, 'PK') && $this->hasOfficeDocumentStructure($file, $extension),
      default => false,
    };
  }

  protected function hasOfficeDocumentStructure(UploadedFile $file, string $extension): bool
  {
    $path = $file->getRealPath();

    if (! $path) {
      return false;
    }

    $zip = new ZipArchive();

    if ($zip->open($path) !== true) {
      return false;
    }

    $requiredEntries = match ($extension) {
      'docx' => ['[Content_Types].xml', 'word/document.xml'],
      'xlsx' => ['[Content_Types].xml', 'xl/workbook.xml'],
      default => [],
    };

    foreach ($requiredEntries as $entry) {
      if ($zip->locateName($entry, ZipArchive::FL_NOCASE) === false) {
        $zip->close();

        return false;
      }
    }

    $zip->close();

    return true;
  }
}

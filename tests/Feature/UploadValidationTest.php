<?php

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function createUploadValidationUser(): User
{
    $role = Role::query()->firstOrCreate(['name' => 'user']);

    return User::factory()->create(['role_id' => $role->id]);
}

function makeZipUpload(string $name, array $entries, string $mime = 'application/zip'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'securedocs-upload-');
    $zip = new ZipArchive();
    $zip->open($path, ZipArchive::OVERWRITE);

    foreach ($entries as $entry => $contents) {
        $zip->addFromString($entry, $contents);
    }

    $zip->close();

    return new UploadedFile($path, $name, $mime, null, true);
}

test('plain zip file renamed as docx is rejected', function () {
    Storage::fake('local');

    $user = createUploadValidationUser();
    $file = makeZipUpload('fake.docx', [
        'payload.txt' => 'not a word document',
    ]);

    $this->actingAs($user)
        ->post('/documents', ['document' => $file])
        ->assertSessionHasErrors('document');
});

test('docx file with expected office structure can be uploaded', function () {
    Storage::fake('local');

    $user = createUploadValidationUser();
    $file = makeZipUpload('valid.docx', [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
        'word/document.xml' => '<?xml version="1.0"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body><w:p/></w:body></w:document>',
    ]);

    $this->actingAs($user)
        ->post('/documents', ['document' => $file])
        ->assertCreated()
        ->assertJsonPath('original_name', 'valid.docx');
});

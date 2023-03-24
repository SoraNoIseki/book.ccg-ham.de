<?php

namespace Soranoiseki\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Soranoiseki\Core\Providers\FileServiceProvider;
use Illuminate\Database\Eloquent\SoftDeletes;
use Intervention\Image\Facades\Image;

/**
 * https://learnku.com/docs/laravel/8.5/filesystem/10388
 */
class File extends Model
{
    use HasFactory;
    use SoftDeletes;

    /**
     * @var string
     */
    protected $table = 'sys_files';
     
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'description',
    ];

    /**
     * @param Illuminate\Http\UploadedFile $file
     * @param string $folder
     * @return File | false
     */
    public function storeUploadFile($file, $folder = '') {
        $this->extension = $file->getClientOriginalExtension();
        $this->name = $this->generateFileName($file->getClientOriginalName(), $this->extension, true);
        $this->mime = $file->getMimeType();
        $this->title = rtrim($file->getClientOriginalName(), '.' . $this->extension);

        // TODO: resize image?

        if (in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif'])) {
            // $this->resizeImage($file, 600, 600);
        }

        // save file to local storage
        try {
            if ($folder !== '') {
                $folder = trim($folder);
                $folder = rtrim($folder, '/') . '/';
            } else {
                if (in_array($this->extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                    $folder = 'images/';
                } else {
                    $folder = 'files/';
                }
            }
            $path = $file->storeAs(
                $folder . date("Y/m/d"),
                $this->name,
                'uploads'
            );
        } catch (\Throwable $th) {
            echo $th->getMessage();
            return false;
        }

        // file path: real path in /storage/
        // use storage($path) to get file
        $this->path = $path;

        // public url: files with /uploads/ route will be always protected
        // defined in Routes/web.php 
        $this->public_url = '/uploads/' . $path;
        
        return $this;
    }

    /**
     * @param string $fileName
     * @param string $fileExtension
     * @param boolean $md5Encode
     * @return string
     */
    protected function generateFileName($fileName, $fileExtension, $md5Encode = false) {
        $fileName = str_replace(' ', '-', $fileName);

        if ($md5Encode) {
            return md5($fileName) . '.' . $fileExtension;
        } else {
            return htmlentities($fileName, ENT_QUOTES, 'UTF-8', false);
        }
    }

    protected function handleDuplicateFileName($fileName) {
        // 
    }
    
}
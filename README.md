# MoonShine Dropzone

**Dropzone.js** integration for the [MoonShine](https://github.com/moonshine-software/moonshine) admin panel.

Based on the fork
https://github.com/NicolasCARPi/dropzone
of the original 
https://github.com/dropzone/dropzone

![screenshot](demo-1.png)

**Description:**
- minimalistic drag‑and‑drop or click-to-choose area
- uploads immediately on file drop (may become an option in the future)
- thumbnail generation for the basic image files
- simple text icon for the unsupported formats
- file removal confirmation
- tries uploading rejected files (e.g. when we remove something and go below max files limit ). Currently only on Remove event.
- currently not actually deleting files on the server

---

## Installation

```bash
composer require moonshine/dropzone
```

Registers Route /moonshine-dropzone

```bash
php artisan vendor:publish --tag=moonshine-dropzone-assets
```

---

## Usage

- Set column as JSON in Model $casts.
- Add a field in your MoonShine resource:

```php
use MoonShine\Dropzone\Fields\Dropzone;

Dropzone::make( 'Images', 'images' )
	->uploadTo( '', '/project_name/images' ) 
	->maxFiles( 6 )
```
- Saves a string in maxFiles( 1 ) mode and array in multiple files mode.



## 📄 License

MIT – see `LICENSE` file.

---
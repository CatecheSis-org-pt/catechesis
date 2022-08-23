/**
 * Sleep time in milliseconds
  */
function sleep(time)
{
    return new Promise((resolve) => setTimeout(resolve, time));
}


/**
 * Restart the cropper.js instance
 * @param cropperInstance
 * @param imageId
 * @param aspectRatio
 */
function restartCropper(cropperInstance, imageId, aspectRatio=null)
{
    if(cropperInstance != null)
        cropperInstance.destroy();

    const image = document.getElementById(imageId);

    if(aspectRatio)
    {
        cropperInstance = new Cropper(image, {
            autoCropArea: 1,
            aspectRatio: aspectRatio,
            crop(event) {
            },
        });
    }
    else
    {
        cropperInstance = new Cropper(image, {
            autoCropArea: 1,
            crop(event) {
            },
        });
    }

    return cropperInstance;
}


/**
 * Download a cropped photo from cropper.js.
 * @param cropperInstance
 * @param fileName
 * @param fileType
 */
function download_photo(cropperInstance, fileName, fileType)
{
    if(fileType === 'image/png')
    {
        //Supports transparency
        imageData = cropperInstance.getCroppedCanvas({
                                                        minWidth: 256,
                                                        minHeight: 256,
                                                        maxWidth: 4096,
                                                        maxHeight: 4096,
                                                        imageSmoothingEnabled: false,
                                                        imageSmoothingQuality: 'high'
                                                    }).toDataURL(fileType);
    }
    else
    {
        // Since transparency is not supported, a fill color is needed
        imageData = cropperInstance.getCroppedCanvas({
                                                        minWidth: 256,
                                                        minHeight: 256,
                                                        maxWidth: 4096,
                                                        maxHeight: 4096,
                                                        fillColor: '#fff',
                                                        imageSmoothingEnabled: false,
                                                        imageSmoothingQuality: 'high'
                                                    }).toDataURL(fileType);
    }
    var link = document.createElement("a");
    link.download = fileName;
    link.href = imageData;
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    delete link;
}
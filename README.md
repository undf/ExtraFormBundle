ExtraFormBundle
===============

This bundle provides you with custom form types and data transformers to use in your forms.


ImageUpload Form Type
---------------------

This form type allow including an image upload field with image preview in your form.

It´s assumed that your entity it is marked as uploadable using the [VichUploaderBundle][1]
and you include [AngularJs][2] in the template which holds the form.

[1]: https://github.com/dustin10/VichUploaderBundle
[2]: http://angularjs.org/


### How to use it

#### 1. Set up your entity:
```php
namespace Acme\YourBundle\Entity

/*
 * @Vich\Uploadable
 */
class YourEntity
{
  ...
  
  /**
   * @Vich\UploadableField(mapping="<your_vich_uploader_mapping>", fileNameProperty="yourImageNameProperty")
   *
   * @var File $yourImageFileProperty
   */
  protected $yourImageFileProperty;

  /**
   * @var string $yourImageNameProperty
   */
  protected $yourImageNameProperty;
  ...
  
  /**
   * Also include this function in your entity, so the parent form can propagate the whole
   * entity to the child form 'image_upload'; this is temporary hack untill we solve this
   * issue in a better way.
   * 
   */
  public function getEntity()
  {
    return $this;
  }
}
```

#### 2. Include the required javascript in your template:
``` javascript
  <script src="{{ asset('bundles/undfform/js/image_upload.js') }}"></script>
```
This will load the AngularJs module uImageUpload

#### 3. Add the module uImageUpload as a dependency in the declaration of your Angular app module:
``` javascript
  angular.module('<your-angular-app>', ["uImageUpload"]);
```

#### 4. Include the form field with type 'image_upload' in your form:
```php
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder
      ->add('entity', 'image_upload', array(
        'data_class' => 'Acme\YourBundle\Entity\YourEntity', // required
        'default_image_url' => '/images/your-default-image-url', // required
        'file_property' => 'yourImageFileProperty', // default = 'image'
        'name_property' => 'yourImageNameProperty', // default = 'imageName'
        'translation_domain' => 'yourTranslationDomain' // default = 'messages'
      ))
    ;
    ...
  }
```




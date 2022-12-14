## Giới thiệu
1. Với botble quản lý file bằng File Manager, với một số trường hợp cần phân quyền thì cần viết custom code để upload file riêng biệt.
2. Với một số case cần viết thêm bảng cho phép thêm các item con vào trong module chính.

Với plugin này cho phép việc tạo code thông qua command, chỉ cần seting theo đúng là có thể sử dụng.

## Cài đặt
1. Tiến hành copy plugin vào thư mục platforms/plugins
2. Trên giao diện quản lý plugin của admin, tiến hành active plugin.
3. Chạy lệnh command:
Lệnh tạo code upload file
``php artisan cms:plugin:make:upload-file-generate {plugin : The plugin name} {name : Model name} {base : Base model name}``

Lệnh tạo module detail
``php artisan cms:plugin:make:module-detail {plugin : The plugin name} {name : Model name} {base : Base model name}``
Với các thông số:
- ``plugin``: tên plugins muốn thêm code upload file
- ``name``: tên của phần code upload file, thường sẽ dựa trên tên này để tạo: Model, variable, input
- ``base``: tên model bạn muốn tạo quan hệ cho bảng file được upload.

4. Tiến hành chỉnh sửa code theo phần đính kèm:
###Dành cho phần generate code upload file
Ví dụ: 
```
php artisan cms:plugin:make:upload-file-generate blog post-file post
------------------
The upload file CRUD for plugin  blog was created in C:\OpenServer\domains\botble.doc\platform/plugins\blog, customize it!
------------------
Application cache cleared!
Add below code into \platform/plugins\blog/config/permissions.php
[
    'name' => 'Post files files',
    'flag' => 'post-file.index',
],
[
    'name'        => 'Create',
    'flag'        => 'post-file.create',
    'parent_flag' => 'post-file.index',
],
[
    'name'        => 'Edit',
    'flag'        => 'post-file.edit',
    'parent_flag' => 'post-file.index',
],
[
    'name'        => 'Delete',
    'flag'        => 'post-file.destroy',
    'parent_flag' => 'post-file.index',
],

Add below code into \platform/plugins\blog/helpers/constants.php
if (!defined('POST_FILE_MODULE_SCREEN_NAME')) {
    define('POST_FILE_MODULE_SCREEN_NAME', 'post-file');
}

Add below code into \platform/plugins\blog/src/Forms/PostForm.php
//Add this code to function buildForm()

->setFormOption('enctype', 'multipart/form-data') after  $this->setupModel(new Post)
Add below code into \platform/plugins\blog/src/Models/Post.php
// Add this code into Post model
/**
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function postFile()
{
    return $this->hasMany('Botble\Blog\Models\PostFile', 'post_id');
}

// Add this code into ::boot function of Post model
protected static function boot()
{
    parent::boot();

    self::deleting(function (Post $post) {
        \PostFile::where('post_id', $post->id)->delete();
    });
}
Add below code into \platform/plugins\blog/src/Providers/BlogServiceProvider.php
// Add into function register()
$this->app->bind(\Botble\Blog\Repositories\Interfaces\PostFileInterface::class, function () {
    return new \Botble\Blog\Repositories\Caches\PostFileCacheDecorator(
        new \Botble\Blog\Repositories\Eloquent\PostFileRepository(new \Botble\Blog\Models\PostFile)
    );
});

// Add into function boot()
Add 'generate', 'file' into ->loadAndPublishConfigurations(['generate', 'file']) after $this->setNamespace

$this->app->register(UploadFileEventServiceProvider::class);
$this->app->register(UploadFileHookServiceProvider::class);
Add below code into \platform/plugins\blog/src/Plugin.php
// Add into function remove()
Schema::dropIfExists('post_files');

Add below code into \platform/plugins\blog/webpack.mix.js
// Add this code to webpack.mix.js
.sass(source + '/resources/assets/sass/post-file-admin.scss', dist + '/css')
.js(source + '/resources/assets/js/post-file-admin.js', dist + '/js')

.copy(source + '/public/images', dist + '/images')

```

### Dành cho phần code thêm module detail
------------------
```
Application cache cleared!
Add below code into \platform/plugins\blog/config/permissions.php
[
    'name' => 'Post details files',
    'flag' => 'post-detail.index',
],
[
    'name'        => 'Create',
    'flag'        => 'post-detail.create',
    'parent_flag' => 'post-detail.index',
],
[
    'name'        => 'Edit',
    'flag'        => 'post-detail.edit',
    'parent_flag' => 'post-detail.index',
],
[
    'name'        => 'Delete',
    'flag'        => 'post-detail.destroy',
    'parent_flag' => 'post-detail.index',
],

Add below code into \platform/plugins\blog/helpers/constants.php
if (!defined('POST_DETAIL_MODULE_SCREEN_NAME')) {
    define('POST_DETAIL_MODULE_SCREEN_NAME', 'post-detail');
}

Add below code into \platform/plugins\blog/routes/web.php
Route::group(['namespace' => 'Botble\Blog\Http\Controllers', 'middleware' => ['web', 'core']], function () {
    Route::group(['prefix' => BaseHelper::getAdminPrefix(), 'middleware' => 'auth'], function () {
        Route::group(['prefix' => 'post', 'as' => 'post.'], function () {
            Route::resource('', 'PostController')->parameters(['' => 'post']);

            // Add this content into Route.php
            Route::delete('items/destroy', [
                'as'         => 'deletes',
                'uses'       => 'PostController@deletes',
                'permission' => 'post-detail.destroy',
            ]);

            Route::post('sorting', [
                'as'         => 'sorting',
                'uses'       => 'PostController@postSorting',
                'permission' => 'post-detail.edit',
            ]);
        });

        Route::group(['prefix' => 'post-details', 'as' => 'post-detail.'], function () {
            Route::resource('', 'PostDetailController')->except([
                'index',
                'destroy',
            ])->parameters(['' => 'post-detail']);

            Route::match(['GET', 'POST'], 'list/{id}', [
                'as'   => 'index',
                'uses' => 'PostDetailController@index',
            ]);

            Route::get('delete/{id}', [
                'as'   => 'destroy',
                'uses' => 'PostDetailController@destroy',
            ]);

            Route::delete('delete/{id}', [
                'as'         => 'delete.post',
                'uses'       => 'PostDetailController@postDelete',
                'permission' => 'post-detail.destroy',
            ]);
        });
    });
});

Add below code into \platform/plugins\blog/src/Forms/PostForm.php
//Add this code to function buildForm()

if ($this->model->id) {
            $this->addMetaBoxes([
                'post-details' => [
                    'title'   => trans('plugins/blog::post-detail.post-details'),
                    'content' => $this->tableBuilder->create(PostDetailTable::class)
                        ->setAjaxUrl(route(
                            'post-detail.index',
                            $this->getModel()->id ?: 0
                        ))
                        ->renderTable(),
                ],
            ]);
        }
Add below code into \platform/plugins\blog/src/Models/Post.php
// Add this code into Post model
/**
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function {+names}()
{
    return $this->hasMany('Botble\Blog\Models\PostDetail', 'post_id')->orderBy('post_details.order');;
}

// Add this code into ::boot function of Post model
protected static function boot()
{
    parent::boot();

    self::deleting(function (Post $post) {
        \PostDetail::where('post_id', $post->id)->delete();
    });
}
Add below code into \platform/plugins\blog/src/Http/Controllers/PostController.php
<?php

use Botble\Blog\Repositories\Interfaces\PostDetailInterface;


/**
 * @var PostDetailInterface;
 */
protected $post detailRepository;

/**
 * PostController constructor.
 * @param PostInterface; $postRepository
 * @param PostDetailInterface; $postDetailRepository
 */
public function __construct(
    PostInterface; $postRepository,
    PostDetailInterface; $postDetailRepository
) {
    $this->postRepository = $postRepository;
    $this->postDetailRepository = $postDetailRepository;
}

/**
 * @param Request $request
 * @param $id
 * @param BaseHttpResponse $response
 * @return array|BaseHttpResponse
 */
public function destroy(Request $request, $id, BaseHttpResponse $response)
{
    try {
        $post = $this->postRepository->findOrFail($id);
        $this->postRepository->delete($post);

        event(new DeletedContentEvent(POST_MODULE_SCREEN_NAME, $request, $post));

        return $response->setMessage(trans('core/base::notices.delete_success_message'));
    } catch (Exception $exception) {
        return $response
            ->setError()
            ->setMessage($exception->getMessage());
    }
}

/**
 * @param Request $request
 * @param BaseHttpResponse $response
 * @return array|BaseHttpResponse|\Illuminate\Http\JsonResponse
 * @throws Exception
 */
public function deletes(Request $request, BaseHttpResponse $response)
{
    return $this->executeDeleteItems($request, $response, $this->postRepository, POST_MODULE_SCREEN_NAME);
}

/**
 * @param Request $request
 * @param BaseHttpResponse $response
 * @return BaseHttpResponse
 */
public function postSorting(Request $request, BaseHttpResponse $response)
{
    foreach ($request->input('items', []) as $key => $id) {
        $this->postDetailRepository->createOrUpdate(['order' => ($key + 1)], ['id' => $id]);
    }

    return $response->setMessage(trans('plugins/blog::post-detail.update_post_position_success'));
}

Add below code into \platform/plugins\blog/src/Providers/BlogServiceProvider.php
// Add into function register()
$this->app->bind(\Botble\Blog\Repositories\Interfaces\PostDetailInterface::class, function () {
    return new \Botble\Blog\Repositories\Caches\PostDetailCacheDecorator(
        new \Botble\Blog\Repositories\Eloquent\PostDetailRepository(new \Botble\Blog\Models\PostDetail)
    );
});
Add below code into \platform/plugins\blog/src/Plugin.php
// Add into function remove()
Schema::dropIfExists('post_details');

Add below code into \platform/plugins\blog/webpack.mix.js
// Add this code to webpack.mix.js
.sass(source + '/resources/assets/sass/post-detail-admin.scss', dist + '/css')
.js(source + '/resources/assets/js/post-detail-admin.js', dist + '/js')
```

5. Tiến hành deactive and reactive lại module được generate code upload file để chạy tạo CSDL và kích hoạt cấu hình.
6. Tiến hành chạy ``npm run dev`` để build js, sass và publish file về folder public.
7. Tiến hành kiểm tra xem chạy ổn chưa.

------------------
## Một số lưu ý:
### Dành cho Generate Upload file
1. Version này dựa trên ý tưởng RVMedia của Botble, được xây dựng thành plugins hỗ trợ tự tạo code nhanh hơn.
2. Phần code này giao diện khá là đơn giản, không có preview image, xóa khỏi danh sách file trước khi upload lên. 
--> Phần này sẽ update sau khi mình tìm được đoạn code cũ.
3. Vì là upload theo form nên chỉ nên upload file nhỏ, không có ajax, không sử dụng chunk upload.
--> hi vọng có bạn nào có thời gian cải thiện giúp.

### Dành cho module detail
1. Phần code này sẽ chỉ hỗ trợ tạo CSDL, controller, view sẵn.
2. Các phần như admin setting, shortcode, css, js cho frontend chưa có, cần viết thêm

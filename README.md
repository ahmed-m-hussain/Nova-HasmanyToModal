
[![GitHub issues](https://img.shields.io/github/issues/ahmed-m-hussain/Nova-HasmanyToModal)](https://github.com/ahmed-m-hussain/Nova-HasmanyToModal/issues)
[![GitHub forks](https://img.shields.io/github/forks/ahmed-m-hussain/Nova-HasmanyToModal)](https://github.com/ahmed-m-hussain/Nova-HasmanyToModal/network)
[![GitHub stars](https://img.shields.io/github/stars/ahmed-m-hussain/Nova-HasmanyToModal)](https://github.com/ahmed-m-hussain/Nova-HasmanyToModal/stargazers)
[![GitHub license](https://img.shields.io/github/license/ahmed-m-hussain/Nova-HasmanyToModal)](https://github.com/ahmed-m-hussain/Nova-HasmanyToModal/blob/master/LICENSE)

# Nova-HasmanyToModal
# Nova 4 Support


install
```bash
composer require ahmed-hussain/hasmany-to-modal
```

<p>
To create a modal for creating or viewing related HasMany records without leaving the index page
</p>
<img src='https://github.com/ahmed-m-hussain/Nova-HasmanyToModal/blob/main/HasmanyToModal.png?raw=true'  alt="index">
<p>Create via Modal don't need leave index to create or view HasMany </p>
<img src='https://github.com/ahmed-m-hussain/Nova-HasmanyToModal/blob/main/HasmanyToModalCreate.png?raw=true'  alt="create">

### Usage
```php
    /**
     * The size of the modal. Can be "sm", "md", "lg", "xl", "2xl", "3xl", "4xl", "5xl", "6xl", "7xl", "full-screen".
     */

use AhmedHussain\HasmanyToModal\HasmanyToModal;

                    HasmanyToModal::make(__('Comments'), 'Comments', Comments::class)
                        ->perPage(50)//count rows show in index
                        ->modalSize('2xl'),

#for Full Screen
                    HasmanyToModal::make(__('Comments'), 'Comments', Comments::class)
                        ->perPage(50)
                        ->modalSize('full-screen'),

```



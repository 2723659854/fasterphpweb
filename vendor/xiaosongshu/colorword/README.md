### php在cli模式输出彩色字体

#### 项目简介
可以在PHP的cli模式下，输出各种颜色的字体。
####安装
```bash 
composer require xiaosongshu/colorword
```
#### 使用
创建index.php，内容如下：
```php 
use Xiaosongshu\Colorword\Transfer;
/** 实例化字体颜色转化类 */
$transfer = new Transfer();
/** 输入需要转换颜色的文字内容，设置文字颜色，设置背景色 */
echo $transfer->getColorString("红字蓝底","red","blue");
echo "\r\n";
echo $transfer->info("提示信息");
echo "\r\n";
echo $transfer->error("错误信息");
echo "\r\n";
echo $transfer->line("普通信息");
echo "\r\n";

```
执行php index.php ，控制台会输出红色文字，背景色为蓝色；
#### 字体颜色
```text
black 黑色
dark_gray 深灰
blue  蓝色
light_blue亮蓝
green绿色
light_green 亮绿
cyan 青色
light_cyan  亮青
red 红
light_red 亮红
purple紫色
light_purple 亮紫
brown 棕色
yellow 黄
light_gray亮灰
white 白色
```
#### 背景色
```text
black 黑色
red 红色
green 绿色
yellow 黄色
blue 蓝色
magenta 紫色
cyan 青色
light_gray 浅灰
```
####联系作者
email：2723659854@qq.com

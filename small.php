<?php
# 参考地址：https://blog.csdn.net/mijichui2153/article/details/131438250
# unpack 和 unpack协议用于自定义传输协议，就是在tcp协议的基础上封装自己的协议，通常 用于rpc协议和ws等协议。
//echo "i am a small file .";
//return "son";

#同时将4部分数据按照分别指定的格式打包为二进制字符串，格式如下。
#①将16进制数据0x1234按照n(无符号短整型(16位，大端字节序))格式打包成二进制字符串
#②将16进制数据0x5678按照v(无符号短整型(16位，小端字节序))格式打包成二进制字符串
#③将10进制数据65按照c(有符号字符)格式打包成二进制字符串
#④将10进制数据66按照c(无符号字符)格式打包成二进制字符串


$binarydata = pack("nvcC", 0x1234, 0x5678, 65, 66);
print_r("packresult:" . $binarydata);  #4xVAB

/**
①知道这个二进制字符串由四部分组成，每部分的格式分别为nvcC；

②上述加了红色背景的就是指定格式，这个要和打包的时候指定的格式保持一致；

③后面紫色的elem1/elem2/elem3/elem4就是你自己取的名字，方便后续对没部分的引用。

④对照输出结果，一目了然。
 */
$elems = unpack("nelem1/velem2/celem3/Celem4", $binarydata);

print_r($elems);

#输出了一个4,分析下这个4是怎么来的。
$binarydata = pack("n", 0x1234);
print_r($binarydata);
print_r("<pre>");


#0x12  Bin(00010010) Dec(18) #查询ascii码表0~31分配给了控制字符;这里的18表示"设备控制2"并不对应可见字符。
#0x34  Bin(00110100) Dec(52) #查询ascii码表对应可打印字符"4"
#结论:显然4就是这么来的。




$binarydata = pack("v", 0x5678);
print_r($binarydata);  #xV
print_r("<pre>");


#0x56 Bin(01010110) Dec(56)   #查询ascii码表对应字符V
#0x78 Bin(01111000) Dec(120)  #查询ascii码表对应字符x
#结论:又因为v表示小端字节序(低尾端,即尾端在低地址);所以先打印的顺序是xV。



$binarydata = pack("c", 65);
print_r($binarydata);
print_r("<pre>");

#Dec(65) #查询ascii码表对应可见字符A
#结论:所以输出了A


$binarydata = pack("c", 66);
print_r($binarydata);
print_r("<pre>");

#Dec(66) #查询ascii码表对应可见字符B
#结论:所以输出了B

# 注：也就是说直接利用print之类的方法直接打印二进制字符串可能并没有什么实际意义。实际能打印出来的只是这些二进制对应的ascii码而已。

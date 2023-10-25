<?php
$s_host = '0.0.0.0';
$i_port = 9501;
$r_listen_socket = socket_create( AF_INET, SOCK_STREAM, SOL_TCP );
socket_set_option( $r_listen_socket, SOL_SOCKET, SO_REUSEADDR, 1 );
socket_bind( $r_listen_socket, $s_host, $i_port );
socket_listen( $r_listen_socket );
// 将$listen_socket设置为非阻塞IO
socket_set_nonblock( $r_listen_socket );

$a_event_array  = array();
$a_client_array = array();

// 创建event-base
$o_event_base  = new \EventBase();
$s_method_name = $o_event_base->getMethod();
if ( 'epoll' != $s_method_name ) {
    exit( "not epoll" );
}

function read_callback( $r_connection_socket, $i_event_flag, $o_event_base ) {
    $s_content = socket_read( $r_connection_socket, 1024 );
    echo "接受到：".$s_content;
    // 在这个客户端连接socket上添加 读事件
    // 当这个客户端连接socket一旦满足可写条件，我们就可以向socket中写数据了
    global $a_event_array;
    global $a_client_array;
    $o_write_event = new \Event( $o_event_base, $r_connection_socket, \Event::WRITE | \Event::PERSIST, write_callback($r_connection_socket,'',['content'=>$s_content]), array(
        'content' => $s_content,
    ) );
    $o_write_event->add();
    $a_event_array[ intval( $r_connection_socket ) ]['write'] = $o_write_event;
}
function write_callback( $r_connection_socket, $i_event_flag, $a_data ) {
    global $a_event_array;
    global $a_client_array;
    $s_content = $a_data['content'];
    foreach( $a_client_array as $r_target_socket ) {
        if ( intval( $r_target_socket ) != intval( $r_connection_socket ) ) {
            socket_write( $r_target_socket, $s_content, strlen( $s_content ) );
        }
    }
    $o_event = $a_event_array[ intval( $r_connection_socket ) ]['write'];
    $o_event->del();
    unset( $a_event_array[ intval( $r_connection_socket ) ]['write'] );
}
function accept_callback( $r_listen_socket, $i_event_flag, $o_event_base ) {
    global $a_event_array;
    global $a_client_array;
    // socket_accept接受连接，生成一个新的socket，一个客户端连接socket
    $r_connection_socket = socket_accept( $r_listen_socket );
    $a_client_array[]    = $r_connection_socket;
    // 在这个客户端连接socket上添加 读事件
    // 也就说 要从客户端连接上读取消息
    $o_read_event = new \Event( $o_event_base, $r_connection_socket, \Event::READ | \Event::PERSIST, read_callback($r_listen_socket,'',$o_event_base), $o_event_base );
    $o_read_event->add();
    $a_event_array[ intval( $r_connection_socket ) ]['read'] = $o_read_event;
}

// 在$listen_socket上添加一个 读事件
// 为啥是读事件？
// 因为$listen_socket上发生事件就是：客户端建立连接
// 所以，应该是读事件
$o_event = new \Event( $o_event_base, $r_listen_socket, \Event::READ | \Event::PERSIST, accept_callback($r_listen_socket,'',$o_event_base), $o_event_base );
$o_event->add();
//$a_event_array[] = $o_event;
$o_event_base->loop();

<?php

/**
 * @purpose 双向链表
 * @note 向GPT学习编程，php本身没有链表，我们这里使用对象构建双向链表
 * @note 这是一组递归的数据
 */
class DoublyNode
{
    /** @var mixed $data 节点的值 */
    public $data;
    /** @var ?DoublyNode $prev 上一个节点 */
    public $prev;
    /** @var ?DoublyNode $next 下一个节点 */
    public $next;

    public function __construct($data)
    {
        $this->data = $data;
        $this->prev = null;
        $this->next = null;
    }
}

/**
 * @purpose 双向链表操作类
 */
class DoublyLinkedList
{
    /** @var ?DoublyNode $head 链表的头部节点 */
    private $head;
    /** @var ?DoublyNode $tail 链表的尾部节点 */
    private $tail;

    public function __construct()
    {
        $this->head = null;
        $this->tail = null;
    }

    /**
     * 在头部插入节点
     * @param $data
     * @return void
     */
    public function insertAtHead($data)
    {
        $newNode = new DoublyNode($data);
        /** 无数据，则初始化 */
        if ($this->head === null) {
            /** 这里没有指定当前节点的父节点和子节点，不会出现根据值删除节点死循环的情况 */
            $this->head = $newNode;
            $this->tail = $newNode;
        } else {
            /**  当前节点的下一个节点为旧head节点 */
            $newNode->next = $this->head;
            /** 旧head节点的上一个节点为新节点 */
            $this->head->prev = $newNode;
            /** 变更新节点为head节点 */
            $this->head = $newNode;
        }
    }

    /**
     * 在尾部插入节点
     * @param $data
     * @return void
     */
    public function insertAtTail($data)
    {
        $newNode = new DoublyNode($data);
        /** 没有数据则初始化 */
        if ($this->tail === null) {
            $this->head = $newNode;
            $this->tail = $newNode;
        } else {
            /** 当前节点的上一个节点是旧tail节点 */
            $newNode->prev = $this->tail;
            /** 旧tail节点的下一个节点是新节点 */
            $this->tail->next = $newNode;
            /** 变更尾节点为新节点 */
            $this->tail = $newNode;
        }
    }

    /**
     * 根据值删除节点
     * @param $data
     * @return void
     */
    public function deleteNode($data)
    {
        $current = $this->head;
        /** 当前节点存在 */
        while ($current) {
            /** 如果值等于节点值 */
            if ($current->data === $data) {
                /** 如果存在前一个节点 */
                if ($current->prev) {
                    /** 那么父节点的子节点更换为当前节点的子节点 */
                    $current->prev->next = $current->next;
                } else {
                    /** 如果当前节点已经是head节点了，那么就更新head节点为当前节点的子节点 */
                    $this->head = $current->next;
                }
                /** 如果当前节点存在子节点 */
                if ($current->next) {
                    /** 那么更新子节点的父节点为 当前节点的父节点 */
                    $current->next->prev = $current->prev;
                } else {
                    /** 如果当前节点是tail节点，那么更新tail节点为当前节点的父节点 */
                    $this->tail = $current->prev;
                }
                /** 删除当前节点 */
                unset($current);
                return;
            }
            /** 切换当前节点为子节点 */
            $current = $current->next;
        }
    }

    /**
     * 查找指定值的节点
     * @param $data
     * @return DoublyNode|null
     */
    public function findNode($data)
    {
        $current = $this->head;
        while ($current) {
            if ($current->data === $data) {
                return $current;
            }
            $current = $current->next;
        }
        return null;
    }

    /**
     * 遍历链表（正向）
     * @return void
     */
    public function traverseForward()
    {
        $current = $this->head;
        while ($current) {
            echo $current->data . " ";
            /** 移动链表指针 */
            $current = $current->next;
        }
        echo "\n";
    }

    /**
     * 遍历链表（反向）
     * @return void
     */
    public function traverseBackward()
    {
        $current = $this->tail;
        while ($current) {
            echo $current->data . " ";
            $current = $current->prev;
        }
        echo "\n";
    }

    /**
     * 修改指定节点的值
     * @param $data
     * @param $newData
     * @return void
     */
    public function modifyNode($data, $newData)
    {
        $current = $this->head;
        while ($current) {
            if ($current->data === $data) {
                $current->data = $newData;
                return;
            }
            /** 移动指针 */
            $current = $current->next;
        }
    }

    /**
     * 计算链表长度
     * @return int
     */
    public function getLength()
    {
        $count = 0;
        $current = $this->head;
        while ($current) {
            $count++;
            $current = $current->next;
        }
        return $count;
    }
}

// 测试双向链表
$list = new DoublyLinkedList();

$list->insertAtHead(10);
$list->insertAtTail(20);
$list->insertAtTail(30);
$list->insertAtTail(31);
$list->insertAtTail(32);
$list->insertAtTail(33);

echo "正向遍历: ";
$list->traverseForward();
echo "反向遍历: ";
$list->traverseBackward();
echo "链表长度: " . $list->getLength() . "\n";
$list->deleteNode(10);
echo "删除节点 10 后正向遍历: ";
$list->traverseForward();
$list->modifyNode(10, 100);
echo "修改节点 10 为 100 后正向遍历: ";
$list->traverseForward();

$foundNode = $list->findNode(33);
var_dump($foundNode);
if ($foundNode) {
    echo "找到节点 30\n";
} else {
    echo "未找到节点 30\n";
}

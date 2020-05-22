<?php

namespace App;

class SchoolRepository
{
    public function __construct()
    {
        session_start();
        if (!array_key_exists('schools', $_SESSION)) {
            $_SESSION['schools'] = [];
        }
    }

    public function all()
    {
        return array_values($_SESSION['schools']);
    }

    public function find(string $id)
    {
        return $_SESSION['schools'][$id];
    }

    public function destroy(string $id)
    {
        unset($_SESSION['schools'][$id]);
    }

    public function save(array $item)
    {
        if (empty($item['name']) || empty($item['body'])) {
            $json = json_encode($item);
            throw new \Exception("Wrong data: {$json}");
        }
        if (!isset($item['id'])) {
            $item['id'] = uniqid();
        }
        $_SESSION['schools'][$item['id']] = $item;
    }
}

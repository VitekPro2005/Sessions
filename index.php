<?php

//CRUD

$dbSource = __DIR__ . '/blogsWithCategories.db';

$db = new PDO("sqlite:$dbSource");


$statement = $db->query("DROP TABLE IF EXISTS posts");
$statement = $db->query("DROP TABLE IF EXISTS categories");

$statement = $db->query('CREATE TABLE IF NOT EXISTS `categories` (
	`id` INTEGER PRIMARY KEY,
	`title` VARCHAR NOT NULL
);');

$statement = $db->query('CREATE TABLE IF NOT EXISTS `posts` (
	`id` integer primary key,
	`title` VARCHAR NOT NULL,
	`content` TEXT NOT NULL,
	`category_id` INTEGER,
	FOREIGN KEY (category_id) REFERENCES categories(id)
);');

$categories = ['Coding', 'Finance', 'Movies'];
$statement = $db->query("INSERT INTO categories (id, title) VALUES (1, 'Coding'), (2, 'Finance'), (3, 'Movies')");

$posts = [
	['title' => 'PHP 101',
	'content' => 'PHP is easy to learn but hard to master.',
	'category_id' => 1],
	['title' => 'Saving for the future',
	'content' => 'How to manage your money to make sure you save enough.',
	'category_id' => 2],
	['title' => 'The Tarantino Effect',
	'content' => 'An impact of one director dedicated to his craft.',
	'category_id' => 3],
];

$statement = $db->query("INSERT INTO posts (title, content, category_id) VALUES 	('PHP 101', 'PHP is easy to learn but hard to master.', 1),
('Saving for the future', 'How to manage your money to make sure you save enough.', 2),
('The Tarantino Effect', 'An impact of one director dedicated to his craft.', 3)");

$statement = $db->prepare("INSERT INTO posts (title, content, category_id) values (:title, :content, :category_id)");
$statement->execute([':title' => 'Заголовок 1', ':content' => 'Text text', ':category_id' => 3]);

print_r($categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC));
print_r($posts = $db->query("SELECT * FROM posts")->fetchAll(PDO::FETCH_ASSOC));

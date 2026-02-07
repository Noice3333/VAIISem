create table users
(
    id       int auto_increment
        primary key,
    username text null,
    password text null,
    name     text null
);

create table posts
(
    id          int auto_increment primary key,
    user_id     int null,
    title       text not null,
    description text not null,
    image       text null,
    latitude    decimal(10,7) not null,
    longitude   decimal(10,7) not null,
    created_at  timestamp default current_timestamp
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

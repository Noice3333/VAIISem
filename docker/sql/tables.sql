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

-- Comments on posts
create table comments
(
    id         int auto_increment primary key,
    post_id    int not null,
    user_id    int null,
    content    text not null,
    created_at timestamp default current_timestamp
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Likes: polymorphic target (e.g. post or comment)
create table likes
(
    id         int auto_increment primary key,
    user_id    int not null,
    target_type varchar(32) not null,
    target_id  int not null,
    created_at timestamp default current_timestamp,
    unique key ux_user_target (user_id, target_type, target_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

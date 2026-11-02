# Архітектура docker-compose версії (LAMP + OpenCart)

```mermaid
flowchart TB
    client([Клієнт / браузер])

    subgraph compose["docker-compose проєкт"]
        lb["lb: nginx (load balancer)\nport 8081 -> 80"]

        subgraph scaleset["scale set (однакові репліки)"]
            app1["opencart1\nApache + PHP 8.3"]
            app2["opencart2\nApache + PHP 8.3"]
            app3["opencart3\nApache + PHP 8.3"]
        end

        install["install\n(one-shot job:\ncli_install.php)"]
        db[("db: mysql:8.0")]
        vol[(shared_config\nnamed volume)]
    end

    client --> lb
    lb -- round robin --> app1
    lb -- round robin --> app2
    lb -- round robin --> app3

    install -- "1. чекає db healthy" --> db
    install -- "2. створює схему + дані" --> db
    install -- "3. пише config.php /\nadmin/config.php" --> vol

    app1 -- "читає конфіг (read-only)" --> vol
    app2 -- "читає конфіг (read-only)" --> vol
    app3 -- "читає конфіг (read-only)" --> vol

    app1 -- "запити до БД" --> db
    app2 -- "запити до БД" --> db
    app3 -- "запити до БД" --> db
```

## Пояснення

- **Fault tolerance / scale set:** `opencart1`, `opencart2`, `opencart3` — три однакові, stateless
  репліки застосунку, зібрані з одного образу (`app/Dockerfile`). Будь-яка з них може впасти —
  `lb` продовжить обслуговувати трафік через решту.
- **Load balancer:** `nginx` (`nginx/nginx.conf`) приймає весь зовнішній трафік на порт 8081 і
  розподіляє запити між трьома репліками round-robin.
- **Одноразова ініціалізація:** сервіс `install` виконує `install/cli_install.php` рівно один раз
  (через `depends_on: condition: service_completed_successfully`), створюючи схему БД і
  `config.php`/`admin/config.php`, та публікує ці конфіги в спільний volume `shared_config`, який
  потім монтують (read-only) усі три репліки — так уникаємо гонки при одночасній установці з
  кількох контейнерів.
- **Стан:** єдине джерело істини — MySQL (`db`), том `db_data` для персистентності. Файловий стан
  (`image/`, `system/storage/`) у кожної репліки локальний і не розділяється — для продакшн-
  варіанту це потребувало б спільного/об'єктного сховища, але для демонстрації масштабування це
  свідомо спрощено.

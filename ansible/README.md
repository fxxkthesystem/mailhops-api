# Ansible ad-hoc

```sh
ansible-playbook -i inventory mailhops.yml -e "env=aws" -u [username]

# Restart services
ansible -i inventory -b --become-user=root -m service -a "name=php-fpm state=restarted"

ansible -i inventory -b --become-user=root -m service -a "name=nginx state=restarted"

```

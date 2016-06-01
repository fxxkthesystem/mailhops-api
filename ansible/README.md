# Ansible ad-hoc

```sh
ansible-playbook -i inventory mailhops.yml -e "env=aws" -u [username]

# Restart services
ansible all -i inventory -b -u centos -m service -a "name=php-fpm state=restarted"

ansible all -i inventory -b -u centos -m service -a "name=nginx state=restarted"

ansible all -i inventory -b -u centos -m shell -a "curl -I https://localhost"
```

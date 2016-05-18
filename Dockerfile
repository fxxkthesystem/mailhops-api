FROM centos:centos7.2.1511

RUN yum install -y \
  git \
  wget \
  gcc \
  libffi-devel \
  python-devel \
  openssl-devel \
  mongodb

RUN wget https://bootstrap.pypa.io/get-pip.py
RUN python get-pip.py

RUN pip install ansible

RUN mkdir /var/www

#RUN git clone https://github.com/avantassel/mailhops-api.git /var/www/mailhops-api
#RUN cd /var/www/mailhops-api && ansible-playbook -i ansible/inventory.sample ansible/mailhops.yml --extra-vars="cron_on=false"

COPY docker/startup.sh startup.sh

ADD ansible /opt/ansible
RUN ansible-playbook -i /opt/ansible/inventory.sample /opt/ansible/mailhops.yml -e "env=docker" -e "cron_on=false"

CMD["startup.sh"]

EXPOSE 80

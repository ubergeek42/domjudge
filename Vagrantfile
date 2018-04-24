# -*- mode: ruby -*-
# vi: set ft=ruby :

# All Vagrant configuration is done below. The "2" in Vagrant.configure
# configures the configuration version (we support older styles for
# backwards compatibility). Please don't change it unless you know what
# you're doing.
Vagrant.configure("2") do |config|
  # The most common configuration options are documented and commented below.
  # For a complete reference, please see the online documentation at
  # https://docs.vagrantup.com.

  # Every Vagrant development environment requires a box. You can search for
  # boxes at https://atlas.hashicorp.com/search.
  config.vm.box = "ubuntu/xenial64"

  # Disable automatic box update checking. If you disable this, then
  # boxes will only be checked for updates when the user runs
  # `vagrant box outdated`. This is not recommended.
  # config.vm.box_check_update = false

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine. In the example below,
  # accessing "localhost:8080" will access port 80 on the guest machine.
  # NOTE: This will enable public access to the opened port
  config.vm.network "forwarded_port", guest: 80, host: 8080

  # Create a forwarded port mapping which allows access to a specific port
  # within the machine from a port on the host machine and only allow access
  # via 127.0.0.1 to disable public access
  # config.vm.network "forwarded_port", guest: 80, host: 8080, host_ip: "127.0.0.1"

  # Create a private network, which allows host-only access to the machine
  # using a specific IP.
  config.vm.network "private_network", ip: "192.168.33.10"

  # Create a public network, which generally matched to bridged network.
  # Bridged networks make the machine appear as another physical device on
  # your network.
  # config.vm.network "public_network"

  # Share an additional folder to the guest VM. The first argument is
  # the path on the host to the actual folder. The second argument is
  # the path on the guest to mount the folder. And the optional third
  # argument is a set of non-required options.
  config.vm.synced_folder "../domjudge/.git", "/domjudge.git"

  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  # Example for VirtualBox:
  #
  config.vm.provider "virtualbox" do |vb|
  #   # Display the VirtualBox GUI when booting the machine
  #   vb.gui = true
  #
  #   # Customize the amount of memory on the VM:
  #   vb.memory = "1024"
      #vb.cpus = 3
  end
  #
  # View the documentation for the provider you are using for more
  # information on available options.

  # Define a Vagrant Push strategy for pushing to Atlas. Other push strategies
  # such as FTP and Heroku are also available. See the documentation at
  # https://docs.vagrantup.com/v2/push/atlas.html for more information.
  # config.push.define "atlas" do |push|
  #   push.app = "YOUR_ATLAS_USERNAME/YOUR_APPLICATION_NAME"
  # end

  # Enable provisioning with a shell script. Additional provisioners such as
  # Puppet, Chef, Ansible, Salt, and Docker are also available. Please see the
  # documentation for more information about their specific syntax and use.
   config.vm.provision "shell", inline: <<-SHELL
      #adduser --disabled-password domjudge
      apt-get update

      # seed root password(as root)
      echo mysql-server-5.1 mysql-server/root_password password root | debconf-set-selections
      echo mysql-server-5.1 mysql-server/root_password_again password root | debconf-set-selections

      # seed phpmyadmin server selection
      echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect apache2" | debconf-set-selections
      echo "phpmyadmin phpmyadmin/dbconfig-install boolean true" | debconf-set-selections
      echo "phpmyadmin phpmyadmin/mysql/admin-user string root" | debconf-set-selections
      echo "phpmyadmin phpmyadmin/mysql/admin-pass password root" | debconf-set-selections
      echo "phpmyadmin phpmyadmin/mysql/app-pass password phpmyadmin" |debconf-set-selections
      echo "phpmyadmin phpmyadmin/app-password-confirm password phpmyadmin" | debconf-set-selections


      # basic domjudge dependencies
      apt-get install -y gcc g++ make zip unzip mysql-server \
        apache2 php php-cli libapache2-mod-php \
        php-gd php-curl php-mysql php-json \
        php-mcrypt php-gmp php-xml php-mbstring \
        bsdmainutils ntp phpmyadmin \
        linuxdoc-tools linuxdoc-tools-text \
        groff texlive-latex-recommended texlive-latex-extra \
        texlive-fonts-recommended texlive-lang-european

      # for bootstrapping from sources
      apt-get install -y automake autoconf

      # Dependencies for the submit client
      apt-get install -y libcurl4-gnutls-dev libjsoncpp-dev libmagic-dev

      # Dependencies for the judgedaemon
      apt-get install -y make sudo debootstrap libcgroup-dev \
        php-cli php-curl php-json procps \
        gcc g++ openjdk-8-jre-headless \
        openjdk-8-jdk ghc fp-compiler

      # download composer from the package manager to save effort
      apt-get install -y composer

      # Extra packages we like to have
      apt-get install -y git vim htop

      git clone https://github.com/domjudge/domjudge $HOME/domjudge
      cd $HOME/domjudge
      make dist
      make maintainer-conf
      make maintainer-install
      ./sql/dj_setup_database -u root -p root install
      make maintainer-postinstall-apache

      useradd -d /nonexistent -g nogroup -s /bin/false domjudge-run-0
      useradd -d /nonexistent -g nogroup -s /bin/false domjudge-run-1
      groupadd domjudge-run

      cp $HOME/domjudge/etc/sudoers-domjudge /etc/sudoers.d/domjudge
      chmod 400 /etc/sudoers.d/sudoers-domjudge
      ./bin/create_cgroups

      ./misc-tools/dj_make_chroot -a amd64
  SHELL
end

### Pre-checks
# Check minimum Vagrant version
Vagrant.require_version ">= 2.0.1"

# Detect host OS for different folder share configuration
module OS
    def OS.windows?
        (/cygwin|mswin|mingw|bccwin|wince|emx/ =~ RUBY_PLATFORM) != nil
    end
end

### VM configuration
Vagrant.configure("2") do |config|

  config.vm.provider "virtualbox"

  # Check available Plugins
  if OS.windows?
      if !Vagrant.has_plugin?('vagrant-winnfsd')
          puts "The vagrant-winnfsd plugin is required. Please install it with \"vagrant plugin install vagrant-winnfsd\""
          exit
      end
  end

  if Vagrant.has_plugin?('vagrant-vbguest')
      config.vbguest.auto_update = true
  end

  # Define base box
  config.vm.box = "debian/stretch64"

  # Virtualbox settings
  config.vm.hostname = "pwg-testing-environment"
  config.vm.network :private_network, ip: "192.168.56.154"

  config.vm.provider "virtualbox" do |vb|
    vb.gui = false
    vb.customize ['modifyvm', :id, '--memory', 2048]
    vb.customize ["modifyvm", :id, "--cpus", 2]
    vb.customize ["modifyvm", :id, "--name", "pwg-testing-environment"]
  end

  # Configure shared folder
  if OS.windows?
    config.vm.synced_folder ".", "/vagrant", type: "nfs"
  else
    config.vm.synced_folder ".", "/vagrant", :owner => "vagrant", :group => "vagrant"
  end

  # Provisioning
  config.vm.provision "ansible_local" do |ansible|
    ansible.playbook = "provisioning/playbook.yml"
    ansible.become = true
    ansible.verbose = ""
  end

  ## Ensure PHP-FPM and Nginx restart after vagrant up
  config.vm.provision "shell", inline: "service php7.2-fpm restart && service nginx restart", run: "always"

end

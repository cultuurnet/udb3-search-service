desc "Build binaries"
task :build => [:clean] do |task|

  system('composer install --no-dev --ignore-platform-reqs --optimize-autoloader --prefer-dist --no-interaction') or exit 1

  FileUtils.rm('features.dist.yml', :force => true)
end

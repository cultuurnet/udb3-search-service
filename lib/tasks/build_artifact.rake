desc "Create a debian package from the binaries."
task :build_artifact do |task|

  basedir        = '/var/www/udb3-search-service'

  calver_version = ENV['PIPELINE_VERSION'].nil? ? Time.now.strftime("%Y.%m.%d.%H%M%S") : ENV['PIPELINE_VERSION']
  git_short_ref  = `git rev-parse --short HEAD`.strip
  version        = ENV['ARTIFACT_VERSION'].nil? ? "#{calver_version}+sha.#{git_short_ref}" : ENV['ARTIFACT_VERSION']
  artifact_name  = 'uitdatabank-search-api'
  vendor         = 'publiq VZW'
  maintainer     = 'Infra publiq <infra@publiq.be>'
  license        = 'Apache-2.0'
  description    = 'UiTdatabank search API'
  source         = 'https://github.com/cultuurnet/udb3-search-service'
  build_url      = ENV['JOB_DISPLAY_URL'].nil? ? "" : ENV['JOB_DISPLAY_URL']

  FileUtils.mkdir_p('pkg')
  FileUtils.touch('config.yml')
  FileUtils.touch('features.yml')

  system("fpm -s dir -t deb -n #{artifact_name} -v #{version} -a all -p pkg \
    -x '.git*' -x pkg -x lib -x Rakefile -x Gemfile -x Gemfile.lock \
    -x .bundle -x Jenkinsfile -x vendor/bundle -x contrib -x Makefile \
    -x tests -x docker -x docker.md -x docker-compose.yml -x .php-cs-fixer.php \
    -x phpcs.xml.dist -x phpstan.neon -x phpunit.xml.dist -x rector.php \
    --prefix #{basedir} \
    --config-files #{basedir}/config.yml \
    --config-files #{basedir}/features.yml \
    --deb-user www-data --deb-group www-data \
    --description '#{description}' --url '#{source}' --vendor '#{vendor}' \
    --license '#{license}' -m '#{maintainer}' \
    --deb-field 'Pipeline-Version: #{calver_version}' \
    --deb-field 'Git-Ref: #{git_short_ref}' \
    --deb-field 'Build-Url: #{build_url}' \
    ."
  ) or exit 1
end

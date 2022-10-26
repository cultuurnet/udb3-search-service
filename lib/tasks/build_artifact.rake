desc "Create a debian package from the binaries."
task :build_artifact do |task|

  calver_version = ENV['PIPELINE_VERSION'].nil? ? Time.now.strftime("%Y.%m.%d.%H%M%S") : ENV['PIPELINE_VERSION']
  git_short_ref  = `git rev-parse --short HEAD`.strip
  version        = ENV['ARTIFACT_VERSION'].nil? ? "#{calver_version}+sha.#{git_short_ref}" : ENV['ARTIFACT_VERSION']
  artifact_name  = 'uitdatabank-search-api'
  vendor         = 'publiq VZW'
  maintainer     = 'Infra publiq <infra@publiq.be>'
  license        = 'Apache-2.0'
  description    = 'UiTdatabank search service'
  source         = 'https://github.com/cultuurnet/udb3-search-service'

  FileUtils.mkdir_p('pkg')
  system('touch config.yml')

  system("fpm -s dir -t deb -n #{artifact_name} -v #{version} -a all -p pkg \
    -x '.git*' -x pkg -x lib -x Rakefile -x Gemfile -x Gemfile.lock \
    -x .bundle -x Jenkinsfile -x vendor/bundle \
    --prefix /var/www/udb3-search-service \
    --config-files /var/www/udb3-search-service/config.yml \
    --deb-user www-data --deb-group www-data \
    --description '#{description}' --url '#{source}' --vendor '#{vendor}' \
    --license '#{license}' -m '#{maintainer}' \
    --before-remove lib/tasks/prerm \
    --deb-systemd lib/tasks/udb3-consume-api.service \
    --deb-systemd lib/tasks/udb3-consume-cli.service \
    --deb-systemd lib/tasks/udb3-consume-related.service \
    --deb-field 'Pipeline-Version: #{calver_version}' \
    --deb-field 'Git-Ref: #{git_short_ref}' \
    ."
  ) or exit 1
end
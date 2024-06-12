desc 'Remove generated files.'
task :clean do |task|
  FileUtils.rm_r('pkg', :force => true)
end

FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

# 配置 NuGet 源
RUN dotnet nuget add source https://api.nuget.org/v3/index.json -n nuget.org || true
RUN dotnet nuget add source https://mirrors.aliyun.com/nuget/v3/index.json -n aliyun || true

COPY *.sln ./
COPY SmsPlatform.Domain/*.csproj ./SmsPlatform.Domain/
COPY SmsPlatform.Application/*.csproj ./SmsPlatform.Application/
COPY SmsPlatform.Infrastructure/*.csproj ./SmsPlatform.Infrastructure/
COPY SmsPlatform.Api/*.csproj ./SmsPlatform.Api/

# 恢复依赖，带超时重试
RUN dotnet restore --verbosity minimal || dotnet restore --verbosity minimal || dotnet restore

COPY . .

# 清理多余的 obj/bin 避免污染
RUN dotnet clean -c Release || true

RUN dotnet publish SmsPlatform.Api/SmsPlatform.Api.csproj -c Release -o /app/publish --no-restore || \
    dotnet publish SmsPlatform.Api/SmsPlatform.Api.csproj -c Release -o /app/publish

# 运行时镜像
FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS runtime
WORKDIR /app

# 创建日志目录
RUN mkdir -p /app/logs

COPY --from=build /app/publish .

ENV ASPNETCORE_URLS=http://+:5000
ENV ASPNETCORE_ENVIRONMENT=Production
EXPOSE 5000

ENTRYPOINT ["dotnet", "SmsPlatform.Api.dll"]
